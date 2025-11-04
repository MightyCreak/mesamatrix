# Mesamatrix documentation

* [How to contribute](#how-to-contribute)
* [Deploy using Docker Compose](#deploy-using-docker-compose)
  * [Run the image](#run-the-image)
  * [Initialize Mesamatrix](#initialize-mesamatrix)
  * [Setup cron](#setup-cron)

## How to contribute

If you want to contribute to the project, please check [CONTRIBUTING.md](/CONTRIBUTING.md).

## Deploy using Docker Compose

Here are the steps to deploy Mesamatrix using a Docker Compose:

1. Run the image
2. Initialize Mesamatrix
3. Setup Cron

### Run the image

The following command will build the image using the [`Dockerfile`](/Dockerfile)
and run it in the background (`-d`) using [`compose.yaml`](/compose.yaml).

The `compose.yaml` file exposes the internal port 80 to the port 5000 (`ports:`),
create a `data` volume and mount it to the `private` directory (`volumes:`),
and ensure it will always restart in case of failure or reboot (`restart:`):

```sh
docker compose up -d --build
```

### Initialize Mesamatrix

Now that Mesamatrix is running in the background, run this command line to
initialize the application:

```sh
docker compose exec webapp sh -c "./mesamatrixctl setup && ./mesamatrixctl parse"
```

### Setup cron

And now that Mesamatrix is initialized, you probably want it to automatically
fetch the `mesa` repository and parse its commits in order to update the data in
your Mesamatrix website.

There is a script that you can run within the container, like so:

```sh
docker compose exec webapp scripts/cron.sh
```

Now what's left to do is to set up a cron job that will run this script
regularly. On your server, run `crontab -e` and add these lines at the end
of the file:

```cron
# Update Mesamatrix
*/15  *  * * *  docker compose exec webapp scripts/cron.sh > /dev/null
```
