#!/usr/bin/env bash

set -euo pipefail

# Key LearnPress guest sessions by client IP instead of the
# lp_session_guest cookie (LP_Settings::is_store_ip_customer). No cookie
# means anonymous responses stay cacheable at the router without the
# header-stripping mu-plugin, which this replaces.
wp option update learn_press_store_ip_customer_session yes
