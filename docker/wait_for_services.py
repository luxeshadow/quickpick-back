"""Delay consuming queued jobs until both local services are ready."""
import json
import os
import sys
import time
import urllib.request

urls = [f"http://127.0.0.1:{os.environ.get('PORT', '10000')}/up",
        'http://127.0.0.1:8001/health']
deadline = time.monotonic() + 120
while time.monotonic() < deadline:
    try:
        for url in urls:
            with urllib.request.urlopen(url, timeout=3) as response:
                if response.status != 200:
                    raise ValueError('not ready')
                if url.endswith('/health') and not json.load(response).get('models_ready'):
                    raise ValueError('models not ready')
        os.execvp(sys.argv[1], sys.argv[1:])
    except (OSError, ValueError):
        time.sleep(2)
sys.exit('Local services not ready; queue worker was not started.')
