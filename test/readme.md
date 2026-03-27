# Cosine Testing

This directory makes use of the [cosine-tests](https://gitlab.internal.2pisoftware.com/2pisoftware/cosine/cosine-tests) library in order
to allow for fully parallel, isolated testing using Docker.

## Setup

Install the node dependencies, playwright browsers and system dependencies:

```
npm i
npx playwright install
npx playwright install-deps
```

## Running tests

To run the tests locally, simply run `npm run test` in this directory.

For local testing (i.e. the `CI` env var is not set), this will mount your local copy of Cosine's `system` and `modules` directories into the docker containers.
It will NOT mount other directories or files (e.g. `web.php`). If you have made a change in them, you should build a Cosine image locally and use that for testing.

### Using a custom Cosine image

To test using a custom docker image, set the `DOCKER_IMAGE` env var.

For example:
```sh
# build our image with a known tag
docker build -t cosine:test .

cd test
# run the tests using that tag
DOCKER_IMAGE=cosine:test npm run test
```