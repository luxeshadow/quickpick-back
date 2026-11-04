from threading import Lock

# One process / one heavy inference at a time, including background photo tasks.
inference_lock = Lock()
