# Zabbix Map Viewer

A FastAPI-based web application for visualizing Zabbix hosts on an interactive map using Leaflet.

## Features

- Interactive map with host markers and clustering
- Host status and inventory details
- Search and filter hosts by status or location
- Execute Zabbix scripts and open host-related links
- Customizable map themes

## Prerequisites

- [Docker](https://www.docker.com/get-started) and [Docker Compose](https://docs.docker.com/compose/)
- Zabbix API credentials (host, user, password)

## Installation

1. **Clone the repository:**
   ```sh
   git clone <your-repo-url>
   cd zbx_map
   ```

2. **Configure environment variables:**

   Create a `.env` file in the project root with the following content:
   ```
   ZBX_HOST=https://your-zabbix-server
   ZBX_USER=your-zabbix-username
   ZBX_PASSWORD=your-zabbix-password
   ```

3. **Build and run the application:**
   ```sh
   make
   ```

   Or, manually:
   ```sh
   docker-compose build
   docker-compose up -d
   ```

4. **Access the application:**

   Open your browser and go to: [http://localhost:8081](http://localhost:8081)

## Development

- To view logs:
  ```sh
  make logs
  ```

- To stop and clean up:
  ```sh
  make clean
  ```

## Project Structure

- `app/` - FastAPI application code
  - `main.py` - Application entry point
  - `modules/` - Utility modules (Zabbix session, cache, etc.)
  - `routes/` - API endpoints
- `static/` - Frontend assets (HTML, CSS, JS, images)
- `Dockerfile`, `docker-compose.yaml` - Containerization files
- `requirements.txt` - Python dependencies
