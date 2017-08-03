#! /bin/bash
# Database reset
cd "$(dirname "$0")"
psql -h taiko -f sql/psql/delete_database.sql storiqone-backend storiq
psql -h taiko -f sql/psql/create_database.sql storiqone-backend storiq
psql -h taiko -f sql/psql/insert_into_database.sql storiqone-backend storiq
# psql -h taiko -f sql/psql/storiqone.sql storiqone-backend storiq
# pg_restore -d storiqone-backend -h taiko -U storiq sql/psql/storiqone.sql
