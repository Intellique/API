#! /bin/bash
# Database reset
cd "$(dirname "$0")"
psql -h taiko -f sql/psql/delete_database.sql storiqone-backend-zakary storiq
psql -h taiko -f sql/psql/create_database.sql storiqone-backend-zakary storiq
psql -h taiko -f sql/psql/insert_into_database.sql storiqone-backend-zakary storiq
# psql -h taiko -f sql/psql/storiqone.sql storiqone-backend-zakary storiq
# pg_restore -d storiqone-backend-zakary -h taiko -U storiq sql/psql/storiqone.sql
