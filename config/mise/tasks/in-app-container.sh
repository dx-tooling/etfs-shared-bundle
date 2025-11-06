#!/usr/bin/env bash
#MISE description="Run a command in the app container"

/usr/bin/env docker compose exec -ti app "$@"
