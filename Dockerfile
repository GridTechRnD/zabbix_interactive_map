FROM python:3.11.9-alpine

WORKDIR /app

COPY requirements.txt .

RUN pip install --no-cache-dir -r requirements.txt

COPY . .

CMD ["hypercorn", "app.main:app", "--bind", "0.0.0.0:8080", "--workers", "4"]
