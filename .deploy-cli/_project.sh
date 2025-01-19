#!/bin/bash

# Author: admin@xoren.io
# Script: _utils.sh
# Link https://github.com/xorenio/deploy-cli.sh
# Description: Functions script.

# Script variables
NOWDATESTAMP="${NOWDATESTAMP:-$(date "+%Y-%m-%d_%H-%M-%S")}"

SCRIPT_NAME="${SCRIPT_NAME:-$(basename "$(test -L "$0" && readlink "$0" || echo "$0")" | sed 's/\.[^.]*$//')}"
SCRIPT="${SCRIPT:-$(basename "$(test -L "$0" && readlink "$0" || echo "$0")")}"
SCRIPT_DIR="${SCRIPT_DIR:-$(cd "$(dirname "$0")" && pwd)}"
SCRIPT_DIR_NAME="${SCRIPT_DIR_NAME:-$(basename "$PWD")}"
SCRIPT_DEBUG=${SCRIPT_DEBUG:-false}

# Terminal starting directory
STARTING_LOCATION=${STARTING_LOCATION:-"$(pwd)"}

# Deployment environment
DEPLOYMENT_ENV=${DEPLOYMENT_ENV:-"production"}

# Enable location targeted deployment
DEPLOYMENT_ENV_LOCATION=${DEPLOYMENT_ENV_LOCATION:-false}

# Deployment location
ISOLOCATION=${ISOLOCATION:-"GB"}
ISOSTATELOCATION=${ISOSTATELOCATION:-""}

# Git repo name
GIT_REPO_NAME="${GIT_REPO_NAME:-$(basename "$(git rev-parse --show-toplevel)")}"

# if using GitHub, Github Details if not ignore
GITHUB_REPO_OWNER="${GITHUB_REPO_OWNER:-$(git remote get-url origin | sed -n 's/.*github.com:\([^/]*\)\/.*/\1/p')}"
GITHUB_REPO_URL="${GITHUB_REPO_URL:-"https://api.github.com/repos/$GITHUB_REPO_OWNER/$GIT_REPO_NAME/commits"}"

SCRIPT_LOG_FILE=${SCRIPT_LOG_FILE:-"${SCRIPT_DIR}/${SCRIPT_NAME}.log"}
SCRIPT_LOG_EMAIL_FILE=${SCRIPT_LOG_EMAIL_FILE:-"$HOME/${SCRIPT_NAME}.mail.log"}
JSON_FILE_NAME=${JSON_FILE_NAME:-"${SCRIPT_DIR}/${SCRIPT_NAME}_${NOWDATESTAMP}.json"}
SCRIPT_RUNNING_FILE=${SCRIPT_RUNNING_FILE:-"${HOME}/${GIT_REPO_NAME}_running.txt"}

LATEST_PROJECT_SHA=${LATEST_PROJECT_SHA:-0}

# START - IMPORT FUNCTIONS
if [[ ! -n "$(type -t _registered)" ]]; then
    if [[ -f "${SCRIPT_DIR}/.${SCRIPT_NAME}/_functions.sh" ]]; then
        # shellcheck source=_functions.sh
        source "${SCRIPT_DIR}/.${SCRIPT_NAME}/_functions.sh"
    fi
fi
# END - IMPORT FUNCTIONS

_registered_project() {
    # This is used for checking is _project.sh has been imported or not
    return 0
}

# Function: _start_project
# Description: Start project.
# Parameters: None
# Returns: None

_start_project() {
    local docker_compose_file="0"

    # Generic docker compose up
    if [[ "$(_is_present docker-compose)" = "1" ]]; then

        cd "$HOME/${GIT_REPO_NAME}" || _exit_script

        docker_compose_file="$(_get_project_docker_compose_file)"

        # Start running deployment
        if [[ "${docker_compose_file}" != "0" ]]; then

            local screen_name="${GIT_REPO_NAME}_start_project"
            ## IF SCREEN PROGRAM IS INSTALL
            if [[ "$(_is_present screen)" = "1" ]]; then

                ## CHECK IF BACKGROUND TASKS ARE STILL RUNNING
                if ! screen -list | grep -q "${screen_name}"; then

                    screen -dmS "${screen_name}"
                    screen -S "${screen_name}" -p 0 -X stuff 'cd '"$HOME"'/'"${GIT_REPO_NAME}"' \n'
                    screen -S "${screen_name}" -p 0 -X stuff 'docker-compose -f '"${docker_compose_file}"' up -d; exit\n'

                else # IF SCREEN FOUND

                    _log_error "Already attempting to start project."
                fi

                sleep 1s
                while screen -list | grep -q "${screen_name}"; do
                    sleep 1s
                done
            else ## IF NO SCREEN PROGRAM

                docker-compose -f "${docker_compose_file}" up -d
            fi

            _log_info "Started docker containers"

        fi
    fi
}

# Function: _stop_project
# Description: Stop project.
# Parameters: None
# Returns: None

_stop_project() {
    local docker_compose_file="0"

    # Generic docker compose down
    if [[ "$(_is_present docker-compose)" = "1" ]]; then

        cd "$HOME/${GIT_REPO_NAME}" || _exit_script

        docker_compose_file="$(_get_project_docker_compose_file)"

        # Stop running deployment
        if [[ "${docker_compose_file}" != "0" ]]; then

            local screen_name="${GIT_REPO_NAME}_stop_project"
            ## IF SCREEN PROGRAM IS INSTALL
            if [[ "$(_is_present screen)" = "1" ]]; then

                ## CHECK IF BACKGROUND TASKS ARE STILL RUNNING
                if ! screen -list | grep -q "${screen_name}"; then

                    screen -dmS "${screen_name}"
                    screen -S "${screen_name}" -p 0 -X stuff 'cd '"$HOME"'/'"${GIT_REPO_NAME}"' \n'
                    screen -S "${screen_name}" -p 0 -X stuff 'docker-compose -f '"${docker_compose_file}"' down; exit\n'

                else # IF SCREEN FOUND

                    _log_error "Already attempting to stop project."
                fi

                sleep 1s
                while screen -list | grep -q "${screen_name}"; do
                    sleep 1s
                done
            else ## IF NO SCREEN PROGRAM

                docker-compose -f "${docker_compose_file}" down
            fi

            _log_info "Stopped docker containers"
        fi
    fi
}
