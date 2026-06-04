FROM mysql:8.0

ENV MYSQL_DATABASE=club_management \
    MYSQL_ROOT_PASSWORD=DB_password105@

COPY db_regen.sql /docker-entrypoint-initdb.d/001_db_regen.sql
COPY seed.sql /docker-entrypoint-initdb.d/002_seed.sql
