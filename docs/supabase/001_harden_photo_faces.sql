-- QuickPick / Supabase
-- Étape 2 : sécurisation et indexation de photo_faces.
-- À exécuter dans le SQL Editor Supabase après sauvegarde de la table.

BEGIN;

-- Chaque embedding doit indiquer précisément le modèle qui l'a produit.
ALTER TABLE public.photo_faces
    ADD COLUMN IF NOT EXISTS model_version VARCHAR(100)
    NOT NULL DEFAULT 'edgeface-xs-v1';

-- Permet de tracer un retraitement ou un upsert.
ALTER TABLE public.photo_faces
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ
    NOT NULL DEFAULT NOW();

-- Empêche Python de recréer le même visage lors d'un retry.
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'photo_faces_photo_face_model_unique'
          AND conrelid = 'public.photo_faces'::regclass
    ) THEN
        ALTER TABLE public.photo_faces
            ADD CONSTRAINT photo_faces_photo_face_model_unique
            UNIQUE (photo_id, face_index, model_version);
    END IF;
END
$$;

-- Accélère le filtrage obligatoire par événement et version du modèle.
CREATE INDEX IF NOT EXISTS photo_faces_event_model_index
    ON public.photo_faces (event_id, model_version);

-- Recherche ANN par similarité cosinus.
CREATE INDEX IF NOT EXISTS photo_faces_embedding_hnsw_index
    ON public.photo_faces
    USING hnsw (embedding public.vector_cosine_ops)
    WITH (m = 16, ef_construction = 64);

-- Les embeddings biométriques ne sont jamais lisibles directement depuis le front.
ALTER TABLE public.photo_faces ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE public.photo_faces FROM anon, authenticated;
REVOKE ALL ON SEQUENCE public.photo_faces_id_seq FROM anon, authenticated;

-- Recherche les meilleures photos d'un invité.
-- La recherche est obligatoirement isolée par event_id et model_version.
CREATE OR REPLACE FUNCTION public.match_event_photo_faces(
    p_event_id BIGINT,
    p_query_embedding public.vector(512),
    p_model_version VARCHAR(100) DEFAULT 'edgeface-xs-v1',
    p_similarity_threshold DOUBLE PRECISION DEFAULT 0.30,
    p_match_count INTEGER DEFAULT 100
)
RETURNS TABLE (
    photo_id BIGINT,
    face_index INTEGER,
    similarity DOUBLE PRECISION
)
LANGUAGE sql
STABLE
SECURITY INVOKER
SET search_path = public
AS $function$
    WITH face_candidates AS (
        SELECT
            pf.photo_id,
            pf.face_index,
            1 - (pf.embedding <=> p_query_embedding) AS similarity
        FROM public.photo_faces AS pf
        WHERE pf.event_id = p_event_id
          AND pf.model_version = p_model_version
          AND 1 - (pf.embedding <=> p_query_embedding) >= p_similarity_threshold
        ORDER BY pf.embedding <=> p_query_embedding
        LIMIT LEAST(GREATEST(p_match_count, 1) * 5, 2500)
    ),
    best_face_per_photo AS (
        SELECT DISTINCT ON (candidate.photo_id)
            candidate.photo_id,
            candidate.face_index,
            candidate.similarity
        FROM face_candidates AS candidate
        ORDER BY candidate.photo_id, candidate.similarity DESC
    )
    SELECT
        best.photo_id,
        best.face_index,
        best.similarity
    FROM best_face_per_photo AS best
    ORDER BY best.similarity DESC
    LIMIT LEAST(GREATEST(p_match_count, 1), 500);
$function$;

-- Seul le rôle serveur Supabase peut appeler la fonction.
REVOKE ALL ON FUNCTION public.match_event_photo_faces(
    BIGINT,
    public.vector,
    VARCHAR,
    DOUBLE PRECISION,
    INTEGER
) FROM PUBLIC, anon, authenticated;

GRANT EXECUTE ON FUNCTION public.match_event_photo_faces(
    BIGINT,
    public.vector,
    VARCHAR,
    DOUBLE PRECISION,
    INTEGER
) TO service_role;

COMMIT;

-- Vérifications manuelles après exécution :
-- SELECT column_name, data_type FROM information_schema.columns
-- WHERE table_schema = 'public' AND table_name = 'photo_faces';
--
-- SELECT indexname, indexdef FROM pg_indexes
-- WHERE schemaname = 'public' AND tablename = 'photo_faces';
