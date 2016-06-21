#!/bin/bash
# Database reset
cd "$(dirname "$0")"
psql -h taiko -f sql/psql/delete_database.sql storiqone-backend-vincent storiq
psql -h taiko -f sql/psql/create_database.sql storiqone-backend-vincent storiq
psql -h taiko -f sql/psql/insert_into_database.sql storiqone-backend-vincent storiq
