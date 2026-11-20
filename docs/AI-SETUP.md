# Fashion Recommendation Assistant

## What works without a model key

Two independent entry points are implemented:

- Bottom-right floating Style assistant: signed-in customers describe preferences and refine them without leaving the current page. Session history stores a bounded conversation and the current filters. No cart prerequisite. Open/close, cross-page conversation persistence and async send/reset are described in FLOATING-CHAT.md.
- Shopping bag: selected items drive automatic complementary-category suggestions; selection changes refresh results, and no selection hides them. Already occupied outfit roles are excluded.

Without configuration the interface says **Catalogue rules**. The bilingual fallback recognises common colour, size, style, occasion, budget and no-skirt/dress phrases; it is not a general language model. Unknown wording may need rephrasing. Budget is explicitly **per product**, not an asserted full-outfit budget. It offers individual pieces and links to variant selection, rather than silently adding products to the bag.

All candidates come from published products and active, stocked variants. Hard preferences and exclusions are applied server-side. New visits refresh returned price, stock and selected-variant availability. No match gives a clear message. Recommendation categories are inferred from category names such as Shirts/Tops, Trousers/Jeans/Skirts, Dresses, Jackets/Blazers, Shoes and Accessories. Unrecognised categories are not guessed into complementary roles.

## Enable a real model

1. Choose a provider and model supporting an OpenAI-compatible Chat Completions endpoint with JSON-object output and max_completion_tokens. The adapter follows the [Chat Completions API reference](https://developers.openai.com/api/reference/resources/chat).
2. Copy `ai.example.php` to `ai.local.php` in the deployed Fashion root. This file is ignored by Git and blocked by Apache .htaccess.
3. Set enabled=true, the HTTPS endpoint, api_key and model. You can instead supply FASHION_AI_KEY and FASHION_AI_MODEL as server environment variables. Never put credentials in JS, forms, public screenshots or reports. Do not send your key in chat.
4. Enable PHP cURL and use a trusted CA bundle. The adapter verifies TLS; do not disable verification to work around a certificate error.
5. Set a spending limit at your provider. Test a simple message, then multi-turn refinement. A configured request sends catalogue descriptions and the bounded user's conversation, not customer/account/order records.

For cart AI explanations, cart_ai_enabled=true caches the unchanged selection/catalogue/model result for ten minutes. Set it false to use automatic rule suggestions in the bag while keeping the model in chat. Chat submits use a short per-session rate limit, a connection timeout, a maximum output token count and response-size cap.

## Model result validation and failure behaviour

The model returns JSON preferences and product IDs/reasons. The server sanitises the preferences, rejects IDs outside the eligible catalogue and duplicates, escapes output, and rechecks current variant availability/prices. A model cannot change database prices, stock, orders or account data. User/catalogue text is passed as untrusted data, not executable commands.

Missing keys use rules. Connection/provider/JSON errors show that AI is unavailable and display rule matches instead. Malformed or unusable selections also fall back explicitly. No-match results never invent products. Secrets/provider response bodies are not exposed to customers.

## What you need to prepare

- A working API key, provider endpoint/model and test budget; none is embedded in this delivery.
- About 20–30 products that can form outfits; accurate categories, colour/size/stock/price, style and occasion tags, and matching photos.
- 8–10 demo requests, including a refinement, budget constraint, exclusion, empty result, cart complement and out-of-stock case.

The live provider has not been called or billed during development. Local tests verify contracts, catalogue filtering, stale-result handling and rule fallback; they do not establish that an unconfigured provider account/model will work.
