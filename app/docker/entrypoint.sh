#!/bin/sh
set -e

if [ ! -x node_modules/.bin/ng ]; then
    npm install
fi

exec npm start -- --host 0.0.0.0 --port 4200 --poll 2000
