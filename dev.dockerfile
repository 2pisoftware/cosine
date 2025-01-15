ARG BASE_IMAGE=ghcr.io/2pisoftware/cosine:develop
FROM $BASE_IMAGE
ENV PHPUNIT=10

# Copy dev tools
COPY .codepipeline/docker/ .codepipeline/docker/

# Run dev tools installer
RUN .codepipeline/docker/cmfive_dev_tools.sh

