# Floating Style Assistant

The storefront chatbot now appears only through the bottom-right floating button. It opens an in-page, non-modal panel. Store navigation/account no longer link to a separate assistant page. Old page=assistant links redirect home with chat=open, which opens the panel automatically.

## Use

1. Click Style assistant at the bottom right of any storefront page.
2. Sign in if prompted. The window stays open after login.
3. Enter an occasion, style, colour, size or per-item budget. Send with Enter or the send button; Shift+Enter adds a line.
4. Refine the request in the same window. Recommended product links open the product page while keeping the conversation available.
5. Close using ×, the launcher, or Escape. Reset using ↺. Reset removes preferences/history but does not bypass the short send cooldown.

The panel does not appear on admin/setup. Open state is per browser tab; conversation is in the signed-in PHP session. Logout clears conversation. No browser history of personal chat content is stored. Javascript is required; a no-script hint is supplied.

## Async API and errors

index.php?page=assistant_api is a JSON endpoint, not a page. GET loads current state. Authenticated CSRF-protected POST accepts message/reset. User and model text is escaped before returning the HTML fragment. Request IDs prevent retransmissions of the same message from invoking the recommender twice within the retained session window. Server limits survive Reset.

Pending sends disable duplicate actions. Connection or non-JSON errors preserve input; Refresh chat retrieves any server-completed response before retry. Session expiry/auth failures update the login prompt or CSRF state. Model failure remains an explicit rule fallback, controlled by existing AI settings. No new credentials or database migration are needed for this UI change.

## Verification

- tests/assistant-service.php: 7 checks for send/response, duplicate handling, rate limiting, reset/cooldown behaviour, message length.
- Independent HTTP fixture: 17 checks for guest/auth state, no standalone page, real catalogue cards, refinements, persistence, duplicate/rate/CSRF checks and escaped markup.
- Headless Edge: 26 desktop/mobile checks for open/login/send, no navigation, product results, sizing/overflow, close/reopen, navigation persistence, Escape focus and interrupted-network recovery. Screenshots inspected.
- No live model account called during this verification. Existing real API configuration is not changed by deployment.
