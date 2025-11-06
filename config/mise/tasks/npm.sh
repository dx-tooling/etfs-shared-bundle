#!/usr/bin/env bash
#MISE description="Run NPM"

mise run in-app-container mise exec node -- npm "$@"
