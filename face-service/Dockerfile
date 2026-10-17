FROM python:3.12-slim-bookworm

ARG YUNET_SHA256=8f2383e4dd3cfbb4553ea8718107fc0423210dc964f9f4280604804ed2552fa4
ARG SFACE_SHA256=0ba9fbfa01b5270c96627c4ef784da859931e02f04419c829e83484087c34e79

RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates curl \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /service

RUN mkdir -p /opt/models \
    && curl -fsSL https://github.com/opencv/opencv_zoo/raw/main/models/face_detection_yunet/face_detection_yunet_2023mar.onnx -o /opt/models/face_detection_yunet_2023mar.onnx \
    && echo "$YUNET_SHA256  /opt/models/face_detection_yunet_2023mar.onnx" | sha256sum -c - \
    && curl -fsSL https://huggingface.co/opencv/face_recognition_sface/resolve/main/face_recognition_sface_2021dec.onnx -o /opt/models/face_recognition_sface_2021dec.onnx \
    && echo "$SFACE_SHA256  /opt/models/face_recognition_sface_2021dec.onnx" | sha256sum -c -

COPY pyproject.toml README.md ./
COPY app ./app
RUN pip install --no-cache-dir .

ENV YUNET_MODEL_PATH=/opt/models/face_detection_yunet_2023mar.onnx \
    YUNET_MODEL_SHA256=8f2383e4dd3cfbb4553ea8718107fc0423210dc964f9f4280604804ed2552fa4 \
    SFACE_MODEL_PATH=/opt/models/face_recognition_sface_2021dec.onnx \
    SFACE_MODEL_SHA256=0ba9fbfa01b5270c96627c4ef784da859931e02f04419c829e83484087c34e79 \
    MODEL_VERSION=sface-2021dec-v1

EXPOSE 8001
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8001"]
