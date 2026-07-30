#!/usr/bin/env bash
# Smoke test: hits key routes on the running app and checks HTTP status codes.
set -uo pipefail

BASE_URL="${SMOKE_BASE_URL:-http://localhost:8081}"
FAILED=0

# route -> expected status code
declare -a ROUTES=(
    "/:200"
    "/about:200"
    "/catalog:200"
    "/reviews:200"
    "/contacts:200"
    "/promotions:200"
    "/cart:200"
    "/login:200"
    "/register:200"
    "/admin:302"
    "/nonexistent:404"
)

echo "Running smoke tests against ${BASE_URL}"
echo "-------------------------------------------"

for entry in "${ROUTES[@]}"; do
    path="${entry%%:*}"
    expected="${entry##*:}"
    actual=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "${BASE_URL}${path}")

    if [ "${actual}" = "${expected}" ]; then
        printf "PASS  %-15s expected=%s actual=%s\n" "${path}" "${expected}" "${actual}"
    else
        printf "FAIL  %-15s expected=%s actual=%s\n" "${path}" "${expected}" "${actual}"
        FAILED=1
    fi
done

echo "-------------------------------------------"
if [ "${FAILED}" -eq 0 ]; then
    echo "All smoke tests passed."
else
    echo "Some smoke tests failed."
fi

exit "${FAILED}"
