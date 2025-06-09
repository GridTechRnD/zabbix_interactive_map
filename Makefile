.PHONY: all build run clean deploy

all: clean build run

build:
	docker-compose build

run:
	docker-compose up -d

clean:
	docker-compose down --rmi all

logs:
	docker-compose logs --follow