#! /bin/bash
# Database reset
cd "$(dirname "$0")"
psql -h veenai -f sql/psql/delete_database.sql storiqone storiq
psql -h veenai -f sql/psql/create_database.sql storiqone storiq
psql -h veenai -f sql/psql/insert_into_database.sql storiqone storiq
# psql -h veenai -f sql/psql/storiqone.sql storiqone-backend storiq
# pg_restore -d storiqone-backend -h veenai -U storiq sql/psql/storiqone.sql
