#!/bin/bash
set -e

# Import univer.sql if exists
if [ -f /docker-entrypoint-initdb.d/univer.sql ]; then
    echo "Importing univer.sql..."
    psql -U postgres -d univer -f /docker-entrypoint-initdb.d/univer.sql
    echo "✅ Schema imported successfully"
else
    echo "⚠️  univer.sql not found, skipping import"
fi
