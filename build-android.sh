#!/usr/bin/env bash

set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$project_root"

export JAVA_HOME="${JAVA_HOME:-/opt/homebrew/opt/openjdk@21/libexec/openjdk.jdk/Contents/Home}"
export ANDROID_HOME="${ANDROID_HOME:-$HOME/Library/Android/sdk}"
export PATH="$JAVA_HOME/bin:$ANDROID_HOME/platform-tools:$PATH"

if [[ ! -x "$JAVA_HOME/bin/java" || ! -x "$JAVA_HOME/bin/keytool" ]]; then
    printf 'Java 21 nije pronađena na: %s\n' "$JAVA_HOME" >&2
    exit 1
fi

if [[ ! -f credentials/app-release-key.jks ]]; then
    printf 'Keystore nije pronađen: credentials/app-release-key.jks\n' >&2
    exit 1
fi

npm run build
php artisan native:release patch --no-interaction
php artisan config:clear --no-interaction
php artisan native:package android --build-type=release --no-tty
