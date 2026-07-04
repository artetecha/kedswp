#!/usr/bin/env bash

set -euo pipefail

# wp-native-php-sessions is Pantheon's workaround for multi-container PHP
# session storage. On single-instance Upsun, PHP's default file sessions
# work; the plugin only added a DB round-trip per session request. Its
# sole consumer here is PMPro's checkout captcha flow, which falls back
# to file sessions transparently.
if wp plugin is-active wp-native-php-sessions; then
	wp plugin deactivate wp-native-php-sessions
fi
