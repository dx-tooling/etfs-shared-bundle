#!/usr/bin/env bash
#MISE description="Run NPM in the ETFS app container"

mise run in-app-container mise exec node -- npm "$@"
