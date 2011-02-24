# 1.0 (02/23/11)

- Light port of the MSG module initiated
- Removed the cookie driver (messages will now be stored in the session)
- Removed support for multiple drivers
- Ditched the singleton pattern (switched to a fully static class)
- Removed the config file
- Removed the userguide (in favor of the README.md file)
- Added magic method __callStatic to enable shortcuts e.g. Hint::error(...)
