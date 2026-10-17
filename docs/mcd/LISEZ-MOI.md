# QuickPick — MCD des tables métier uniquement
État : 17 octobre 2026. Source principale : métadonnées en lecture seule de PostgreSQL local ; migrations et modèles Laravel. Périmètre : 14 tables métier locales et photo_faces dans Supabase. Aucun enregistrement utilisateur exporté.
Ouvrir QuickPick-MCD.drawio dans diagrams.net via Fichier > Ouvrir depuis > Appareil. Les objets et associations sont modifiables.
## Convention
Une seule page : 15 blocs reliés, sans onglets multiples. Vue relationnelle des tables métier : PK = identifiant ; FK = référence ; ? = nullable. Cardinalités placées près de chaque bloc : 0,N = zéro à plusieurs ; 1,1 = exactement un ; 0,1 = facultatif.
Les tables techniques Laravel, Sanctum et Spatie sont exclues. users reste présente car elle porte les comptes QuickPick et les relations métier. En orange : photo_faces et ses liens logiques Supabase. Aperçu : QuickPick-MCD-une-page.png.
## Limites et règles réelles
- La table Supabase photo_faces est distincte de PostgreSQL local. Sa structure provient du schéma fourni et du script docs/supabase/002_sface_search.sql ; elle n’a pas été inspectée directement pour ce document.
- Les liens Supabase photo_id → media.id et event_id → events.id sont logiques, sans FK inter-base.
- guest_scan_matches stocke la meilleure face par média et par scan (paire unique), pas toutes les faces détectées.
- Les livraisons peuvent référencer un lien, un scan, les deux ou aucun selon les contraintes SQL ; la logique métier doit limiter ces cas.
- Un seul abonnement courant n’est pas garanti par une contrainte unique SQL. La sélection dépend du statut et des dates.
- Seuls events et subscription_plans possèdent deleted_at dans le schéma inspecté.
- Supabase Storage contient les fichiers ; PostgreSQL conserve leurs chemins, pas leurs octets.
- Les attributs conceptuels sont sélectionnés pour la lisibilité ; le dictionnaire et les onglets physiques listent toutes les colonnes.
## Dictionnaire physique exhaustif
### users
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| full_name | varchar | non |
| email | varchar | non |
| phone | varchar | oui |
| email_verified_at | timestamp | oui |
| password | varchar | oui |
| google_id | varchar | oui |
| apple_id | varchar | oui |
| status | varchar | non |
| remember_token | varchar | oui |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |
- UNIQUE : apple_id.
- UNIQUE : email.
- UNIQUE : google_id.
- UNIQUE : phone.
- PK : id.

### subscription_plans
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |
| name | varchar | non |
| description | text | oui |
| price | numeric | non |
| duration_days | int4 | oui |
| max_events | int4 | oui |
| max_photos | int4 | oui |
| is_active | bool | non |
| accept_video | bool | non |
| max_videos | int4 | oui |
| deleted_at | timestamp | oui |
- UNIQUE : name.
- PK : id.

### subscriptions
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |
| user_id | int8 | non |
| subscription_plan_id | int8 | non |
| status | varchar | non |
| starts_at | timestamp | non |
| ends_at | timestamp | oui |
| cancelled_at | timestamp | oui |

- FK subscription_plan_id → subscription_plans(id), suppression : restrict.

- FK user_id → users(id), suppression : cascade.
- PK : id.

### payments
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |
| user_id | int8 | non |
| subscription_id | int8 | non |
| provider | varchar | non |
| payment_method | varchar | non |
| amount | numeric | non |
| currency | bpchar | non |
| status | varchar | non |
| external_reference | varchar | oui |
| paid_at | timestamp | oui |

- FK subscription_id → subscriptions(id), suppression : restrict.

- FK user_id → users(id), suppression : cascade.
- UNIQUE : external_reference.
- PK : id.

### invoices
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |
| user_id | int8 | non |
| subscription_id | int8 | non |
| payment_id | int8 | non |
| invoice_number | varchar | non |
| subtotal | numeric | non |
| tax | numeric | non |
| total | numeric | non |
| currency | bpchar | non |
| status | varchar | non |
| issued_at | timestamp | non |
| due_at | timestamp | oui |
| paid_at | timestamp | oui |

- FK payment_id → payments(id), suppression : restrict.

- FK subscription_id → subscriptions(id), suppression : restrict.

- FK user_id → users(id), suppression : cascade.
- UNIQUE : invoice_number.
- UNIQUE : payment_id.
- PK : id.

### events
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| user_id | int8 | non |
| name | varchar | non |
| description | text | oui |
| location | varchar | oui |
| event_date | timestamp | oui |
| status | varchar | non |
| media_count | int4 | non |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |
| deleted_at | timestamp | oui |
| media_status | varchar | non |
| expected_media_count | int4 | non |
| uploaded_media_count | int4 | non |
| processed_media_count | int4 | non |
| failed_media_count | int4 | non |
| upload_started_at | timestamp | oui |
| upload_completed_at | timestamp | oui |
| processing_completed_at | timestamp | oui |

- FK user_id → users(id), suppression : cascade.
- PK : id.
- UNIQUE : user_id, name.

### media
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| event_id | int8 | non |
| storage_path | varchar | non |
| original_filename | varchar | non |
| mime_type | varchar | oui |
| file_size | int8 | oui |
| width | int4 | oui |
| height | int4 | oui |
| processing_status | varchar | non |
| faces_count | int4 | non |
| processing_error | text | oui |
| processing_started_at | timestamp | oui |
| processed_at | timestamp | oui |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |
| uploaded_at | timestamp | oui |
| media_type | varchar | non |
| processing_job_id | uuid | oui |
| processing_attempt | int4 | non |
| model_version | varchar | oui |

- FK event_id → events(id), suppression : cascade.
- PK : id.
- UNIQUE : processing_job_id.

### access_links
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| event_id | int8 | non |
| token | varchar | non |
| expires_at | timestamp | non |
| is_active | bool | non |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |
| created_by | int8 | oui |
| source | varchar | non |
| max_scans | int4 | oui |
| scans_count | int4 | non |
| last_used_at | timestamp | oui |

- FK created_by → users(id), suppression : set null.

- FK event_id → events(id), suppression : cascade.
- PK : id.
- UNIQUE : token.

### guest_scans
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| event_id | int8 | non |
| access_link_id | int8 | oui |
| status | varchar | non |
| matches_count | int4 | non |
| best_similarity | numeric | oui |
| error_message | text | oui |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |

- FK access_link_id → access_links(id), suppression : set null.

- FK event_id → events(id), suppression : cascade.
- PK : id.

### guest_scan_matches
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| guest_scan_id | int8 | non |
| media_id | int8 | non |
| similarity | numeric | non |
| rank | int4 | non |
| face_index | int4 | oui |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |

- FK guest_scan_id → guest_scans(id), suppression : cascade.

- FK media_id → media(id), suppression : cascade.
- UNIQUE : guest_scan_id, media_id.
- PK : id.

### media_deliveries
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| guest_scan_id | int8 | oui |
| channel | varchar | non |
| recipient | varchar | non |
| status | varchar | non |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |
| access_link_id | int8 | oui |
| error_message | text | oui |
| sent_at | timestamp | oui |

- FK access_link_id → access_links(id), suppression : cascade.

- FK guest_scan_id → guest_scans(id), suppression : cascade.
- PK : id.

### media_delivery_items
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| media_delivery_id | int8 | non |
| media_id | int8 | non |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |

- FK media_delivery_id → media_deliveries(id), suppression : cascade.

- FK media_id → media(id), suppression : cascade.
- UNIQUE : media_delivery_id, media_id.
- PK : id.

### upload_batches
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| event_id | int8 | non |
| created_by | int8 | non |
| status | varchar | non |
| expected_count | int4 | non |
| uploaded_count | int4 | non |
| failed_count | int4 | non |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |

- FK created_by → users(id), suppression : cascade.

- FK event_id → events(id), suppression : cascade.
- PK : id.

### upload_batch_items
| Colonne | Type | Nullable |
|---|---|---|
| id | int8 | non |
| upload_batch_id | int8 | non |
| media_id | int8 | oui |
| filename | varchar | non |
| status | varchar | non |
| error | text | oui |
| created_at | timestamp | oui |
| updated_at | timestamp | oui |

- FK media_id → media(id), suppression : set null.

- FK upload_batch_id → upload_batches(id), suppression : cascade.
- PK : id.

