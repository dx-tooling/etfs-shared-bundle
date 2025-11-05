#!/usr/bin/env bash
#MISE description="Run a command in the ETFS app container"

/usr/bin/env docker compose exec -ti app "$@"
