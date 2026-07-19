# Production Hardening Notes

V2 remains local-only during development. Do not expose it through Cloudflare, router port forwarding, or any public hostname until cross-user isolation tests pass in the target deployment.

## Local Development

- Bind web traffic to `127.0.0.1:8081`.
- Use `job_dashboard_v2` only.
- Keep `APP_ENV=local` and `APP_DEBUG=true` only for local development.
- Use `QUEUE_CONNECTION=database` when the `jobsearch-v2-worker` container is running.
- Browser state-changing routes use Laravel CSRF protection.
- Token-based automation should use `/api/legacy-dashboard`, not the browser `/api.php` route.

## Production Defaults

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Set `APP_URL` to the final HTTPS URL only after cutover approval.
- Set `SESSION_SECURE_COOKIE=true` when HTTPS is enabled.
- Set `SESSION_ENCRYPT=true`.
- Keep generated documents on the `local` private disk, outside `public/`.
- Do not log source tokens, passwords, resume content, profile text, or generated document content.
- Store source credentials through `source_connections.encrypted_credentials`, which is encrypted by Laravel casts.

## Deferred Cloudflare Work

Cloudflare trusted proxy and hostname configuration is intentionally deferred. Configure it only after the multi-user isolation suite passes against the deployment target and before enabling the tunnel.
