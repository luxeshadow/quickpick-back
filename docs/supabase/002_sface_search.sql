-- Run in Supabase SQL Editor. Does not change vector(512) or relabel old vectors.
BEGIN;
ALTER TABLE public.photo_faces ADD COLUMN IF NOT EXISTS model_version varchar(100);
CREATE INDEX IF NOT EXISTS photo_faces_event_model_index ON public.photo_faces(event_id, model_version);
DO $migration$
DECLARE vector_schema text;
BEGIN
  SELECT n.nspname INTO vector_schema FROM pg_extension e
    JOIN pg_namespace n ON n.oid = e.extnamespace WHERE e.extname = 'vector';
  IF vector_schema IS NULL THEN RAISE EXCEPTION 'pgvector must be installed'; END IF;
  EXECUTE format($sql$
    CREATE OR REPLACE FUNCTION public.match_event_photo_faces(
      p_event_id bigint, p_query_embedding %1$I.vector(512),
      p_model_version varchar(100) DEFAULT 'sface-2021dec-v1',
      p_similarity_threshold double precision DEFAULT 0.363,
      p_match_count integer DEFAULT 100
    ) RETURNS TABLE(photo_id bigint, face_index integer, similarity double precision)
    LANGUAGE sql STABLE SECURITY INVOKER SET search_path = public, %1$I AS $fn$
      WITH best AS (
        SELECT DISTINCT ON (pf.photo_id) pf.photo_id, pf.face_index::integer,
          (1 - (pf.embedding <=> p_query_embedding))::double precision AS similarity
        FROM public.photo_faces pf
        WHERE pf.event_id = p_event_id AND pf.model_version = p_model_version
          AND 1 - (pf.embedding <=> p_query_embedding) >= p_similarity_threshold
        ORDER BY pf.photo_id, pf.embedding <=> p_query_embedding, pf.face_index
      ) SELECT best.photo_id, best.face_index, best.similarity FROM best
        ORDER BY best.similarity DESC, best.photo_id
        LIMIT LEAST(GREATEST(p_match_count, 1), 500);
    $fn$;
  $sql$, vector_schema);
  EXECUTE format('REVOKE ALL ON FUNCTION public.match_event_photo_faces(bigint,%I.vector,varchar,double precision,integer) FROM PUBLIC, anon, authenticated', vector_schema);
  EXECUTE format('GRANT EXECUTE ON FUNCTION public.match_event_photo_faces(bigint,%I.vector,varchar,double precision,integer) TO service_role', vector_schema);
END $migration$;
ALTER TABLE public.photo_faces ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE public.photo_faces FROM anon, authenticated;
GRANT SELECT, INSERT, DELETE ON TABLE public.photo_faces TO service_role;
COMMIT;
-- Existing vectors with NULL or edgeface-xs-v1 are deliberately excluded.
-- Reprocess their photos with SFace rather than changing their version label.
