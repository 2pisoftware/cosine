# Cosine Frontend

This folder contains frontend code included on every page.
It provides common functionality for things like charts, autocomplete, theme toggles, and more.

## Structure

When the document is loaded or when a `dom-update` event is received, `ready` is called which attaches all our event listeners and performs any other functionality.

## Events

There are a number of events emitted/listened to on the `window.cmfiveEventBus` object, described below.

- `theme-change`. Triggered after the theme has changed. The current theme value (`"dark" | "light"`) is provided as `detail.theme`.
- `modal-load`. Triggered after the modal has been fully loaded and all it's scripts have executed, but BEFORE ready has been called.
- `dom-update` When emitted, causes ready to re-run.

## Features

TBD documentation about each class