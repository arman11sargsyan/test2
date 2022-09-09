# restore-marketplace

## Development environment

To build the image:

    docker compose build

To run the containers:

    docker compose up

The first time you run the containers, the database will be empty and it will need to be loaded.

To do this, in another terminal:

    docker exec -ti restore-marketplace-mysql-1 /bin/bash
    cd /mnt
    unxz initial.sql.xz
    mysql -u restore -prestore -D restore
    mysql> source initial.sql
    mysql> source domain.sql
# test2
