# AI tools — Agent Toolkit + MCP Server

PayPal exposes its API surface to LLM agents two ways:

1. **Agent Toolkit** — a Node/TypeScript or Python library you embed in your agent code. Wraps PayPal capabilities as LLM-callable tools for popular frameworks (LangChain, OpenAI Agents SDK, Vercel AI SDK, CrewAI, Bedrock).
2. **MCP Server** — a Model Context Protocol server (local `npx` or remote-hosted) that any MCP-compatible client (Claude Desktop, Cursor, Cline, Claude Code) can connect to.

Both surface the same underlying tool catalog. Use the toolkit when building custom agents in code; use the MCP server when the AI experience lives inside a chat client.

Docs: https://docs.paypal.ai/

## Agent Toolkit

### Languages + frameworks

| Framework | TypeScript | Python |
|---|---|---|
| Vercel AI SDK | `@paypal/agent-toolkit/ai-sdk` | — |
| OpenAI Agents SDK | `@paypal/agent-toolkit/openai` | `paypal_agent_toolkit.openai` |
| LangChain | `@paypal/agent-toolkit/langchain` | `paypal_agent_toolkit.langchain` |
| CrewAI | — | `paypal_agent_toolkit.crewai` |
| Amazon Bedrock | `@paypal/agent-toolkit/bedrock` | `paypal_agent_toolkit.bedrock` |

Requirements: Node 18+ (TS), Python 3.11+ (Py).

### Install

```bash
# TypeScript / Node
npm install @paypal/agent-toolkit

# Python
pip install paypal-agent-toolkit
```

### Capability gating (configuration.actions)

Both languages take a `configuration` object that declares which capabilities to expose. From the source `tools.ts`:

```javascript
configuration: {
  actions: {
    invoices:          { create, list, send, sendReminder, cancel, generateQRC },
    products:          { create, list, update },
    subscriptionPlans: { create, list, show },
    shipment:          { create, show, cancel },
    orders:            { create, get },
    disputes:          { list, get },
  },
  context: { sandbox: true }   // optional
}
```

Each leaf is a boolean. `ALL_TOOLS_ENABLED` is exported as a shortcut. The actual catalog is broader than these example keys — see "Tool catalog" below.

### TypeScript: Vercel AI SDK example

```typescript
import { openai } from '@ai-sdk/openai';
import { generateText } from 'ai';
import { PayPalAgentToolkit, ALL_TOOLS_ENABLED } from '@paypal/agent-toolkit/ai-sdk';

const paypalToolkit = new PayPalAgentToolkit({
  clientId:     process.env.PAYPAL_CLIENT_ID!,
  clientSecret: process.env.PAYPAL_CLIENT_SECRET!,
  configuration: { actions: ALL_TOOLS_ENABLED }
});

const { text } = await generateText({
  model: openai('gpt-4o'),
  tools: paypalToolkit.getTools(),
  maxSteps: 10,
  prompt: "Retrieve the details of the order with ID 4A572180UY881681N",
});
```

`PayPalWorkflows` is also exported under `/ai-sdk` — a higher-level helper with methods like `generateOrder(llm, userPrompt, systemPrompt)`.

### TypeScript: OpenAI Agents SDK

```typescript
import OpenAI from "openai";
import { PayPalAgentToolkit, ALL_TOOLS_ENABLED } from "@paypal/agent-toolkit/openai";

const llm = new OpenAI();
const paypalToolkit = new PayPalAgentToolkit({
  clientId:     process.env.PAYPAL_CLIENT_ID!,
  clientSecret: process.env.PAYPAL_CLIENT_SECRET!,
  configuration: { actions: ALL_TOOLS_ENABLED },
});

let messages = [{ role: "user", content: "Create a PayPal order for $50 for Premium News service." }];
while (true) {
  const completion = await llm.chat.completions.create({
    model: "gpt-4o",
    messages,
    tools: paypalToolkit.getTools(),
  });
  const reply = completion.choices[0].message;
  messages.push(reply);
  if (reply.tool_calls) {
    const toolMessages = await Promise.all(
      reply.tool_calls.map((tc) => paypalToolkit.handleToolCall(tc))
    );
    messages = [...messages, ...toolMessages];
  } else { break; }
}
```

### TypeScript: LangChain (LangGraph)

```typescript
import { ChatOpenAI } from '@langchain/openai';
import { createReactAgent } from '@langchain/langgraph/prebuilt';
import { PayPalAgentToolkit, ALL_TOOLS_ENABLED } from '@paypal/agent-toolkit/langchain';

const llm = new ChatOpenAI({ temperature: 0.3, model: 'gpt-4o' });
const paypalToolkit = new PayPalAgentToolkit({
  clientId:     process.env.PAYPAL_CLIENT_ID!,
  clientSecret: process.env.PAYPAL_CLIENT_SECRET!,
  configuration: { actions: ALL_TOOLS_ENABLED }
});

const agent = createReactAgent({ llm, tools: paypalToolkit.getTools() });
const result = await agent.invoke({
  messages: [{ role: "user", content: "Create a PayPal order for $50 for Premium News service." }]
});
```

### Python: LangChain

```python
from langchain.agents import initialize_agent, AgentType
from paypal_agent_toolkit.langchain.toolkit import PayPalToolkit
from paypal_agent_toolkit.shared.configuration import Configuration, Context

configuration = Configuration(
    actions={"orders": {"create": True, "get": True}, "invoices": {"create": True, "send": True}},
    context=Context(sandbox=True)
)
toolkit = PayPalToolkit(
    client_id=os.environ["PAYPAL_CLIENT_ID"],
    secret=os.environ["PAYPAL_CLIENT_SECRET"],
    configuration=configuration
)

agent = initialize_agent(
    tools=toolkit.get_tools(),
    llm=llm,
    agent=AgentType.OPENAI_FUNCTIONS,
    verbose=True
)
agent.run("Create a PayPal order for $50 for Premium News service.")
```

Python uses `secret` not `clientSecret`. Sandbox via `Context(sandbox=True)`.

### Python: CrewAI

```python
from crewai import Agent, Crew, Task
from paypal_agent_toolkit.crewai.toolkit import PayPalToolkit
from paypal_agent_toolkit.shared.configuration import Configuration, Context

toolkit = PayPalToolkit(
    client_id=os.environ["PAYPAL_CLIENT_ID"],
    secret=os.environ["PAYPAL_CLIENT_SECRET"],
    configuration=Configuration(actions={"invoices": {"create": True, "send": True}}, context=Context(sandbox=True))
)

agent = Agent(
    role="PayPal Invoicing Assistant",
    goal="Help create and send invoices to customers",
    backstory="You are a helpful invoicing agent.",
    tools=toolkit.get_tools(),
    allow_delegation=False
)
task = Task(description="Create and send an invoice for $200 to customer@example.com",
            expected_output="The sent invoice ID and the payer-view URL",
            agent=agent)
crew = Crew(agents=[agent], tasks=[task], verbose=True)
result = crew.kickoff()
```

## MCP Server

Two modes covering the same tool catalog plus (in remote mode) an extra commerce/cart tool set.

### Mode 1 — Local (`@paypal/mcp`)

Runs on the user's machine, no remote dependency. Requires Node 18+. Invoked via `npx`:

```bash
npx -y @paypal/mcp --tools=all
```

Connect from Claude Desktop — edit `~/Library/Application Support/Claude/claude_desktop_config.json` (Mac) or `%APPDATA%\Claude\claude_desktop_config.json` (Windows):

```json
{
  "mcpServers": {
    "paypal": {
      "command": "npx",
      "args": ["-y", "@paypal/mcp", "--tools=all"],
      "env": {
        "PAYPAL_ACCESS_TOKEN": "YOUR_PAYPAL_ACCESS_TOKEN",
        "PAYPAL_ENVIRONMENT": "SANDBOX"
      }
    }
  }
}
```

`PAYPAL_ENVIRONMENT` accepts `SANDBOX` or `PRODUCTION`.

`PAYPAL_ACCESS_TOKEN` is the OAuth2 access token — fetch one with the standard `POST /v1/oauth2/token` call (see SKILL.md). The token expires (~8h); the user will need to refresh and update the config periodically. (Some PayPal AI doc paths suggest the local server can derive a token from client_id + secret env vars; verify by reading the package source if your user needs auto-refresh.)

`--tools=all` enables every tool. Subset filter syntax beyond `all` is not documented on the public docs.paypal.ai pages — refer users to the package source if they need to enable a subset.

### Mode 2 — Remote (hosted)

Zero install. PayPal hosts an MCP endpoint at:
- Sandbox: `https://mcp.sandbox.paypal.com`
- Production: `https://mcp.paypal.com`

Two transports:
- SSE: `/sse`
- Streamable HTTP: `/http`

Auth: Bearer token in `Authorization` header (a PayPal access token derived from client_id + secret).

Connect via the `mcp-remote` bridge (works with any MCP client that doesn't yet speak HTTP/SSE natively):

```json
{
  "mcpServers": {
    "paypal-mcp-server": {
      "command": "npx",
      "args": [
        "mcp-remote",
        "https://mcp.sandbox.paypal.com/sse",
        "--header", "Authorization: Bearer YOUR_PAYPAL_ACCESS_TOKEN"
      ]
    }
  }
}
```

Add `--header "x-feature-flags: commerce:true"` to enable the commerce/cart tools (search_product, create_cart, checkout_cart).

For direct API integration with LLM SDKs that natively speak MCP:

**Anthropic SDK**:
```python
client.messages.create(
    model="claude-opus-4-7",
    max_tokens=1024,
    extra_headers={"anthropic-beta": "mcp-client-2025-04-04"},
    messages=[{"role": "user", "content": "Create a $50 invoice for ..."}],
    mcp_servers=[{
        "type": "url",
        "url": "https://mcp.paypal.com/sse",
        "name": "paypal",
        "authorization_token": "YOUR_PAYPAL_ACCESS_TOKEN"
    }]
)
```

**OpenAI Responses API**:
```python
client.responses.create(
    model="gpt-4.1",
    input="Create a $50 invoice for ...",
    tools=[{
        "type": "mcp",
        "server_url": "https://mcp.paypal.com/sse",
        "headers": {"Authorization": "Bearer YOUR_PAYPAL_ACCESS_TOKEN"}
    }]
)
```

### Tool catalog (verified from agent-tools-ref)

**Catalog**: `create_product`, `list_products`, `show_product_details`
**Disputes**: `list_disputes`, `get_dispute`, `accept_dispute_claim`
**Invoices**: `create_invoice`, `list_invoices`, `get_invoice`, `send_invoice`, `send_invoice_reminder`, `cancel_sent_invoice`, `generate_invoice_qr_code`
**Orders**: `create_order`, `get_order`, `pay_order`, `create_refund`, `get_refund`
**Reporting**: `get_merchant_insights`, `list_transactions`
**Shipment Tracking**: `create_shipment_tracking`, `get_shipment_tracking`, `update_shipment_tracking`
**Subscriptions**: `create_subscription_plan`, `list_subscription_plans`, `show_subscription_plan_details`, `create_subscription`, `show_subscription_details`, `update_subscription`, `cancel_subscription`, `update_plan`
**Commerce (remote-only, `x-feature-flags: commerce:true`)**: `search_product` (gift cards), `create_cart`, `checkout_cart`

Note: pluralization between docs and source can differ slightly (`list_product` vs `list_products`, `list_transaction` vs `list_transactions`). Source-of-truth is the package; surface both spellings when debugging mismatches.

### Supported MCP clients

PayPal explicitly names: **Claude Desktop, Cursor, Cline**, plus "any MCP-compatible client." Other clients (Claude Code, VS Code with MCP, Windsurf) work via the same `mcp-remote` bridge or local `npx` config but aren't explicitly endorsed in PayPal's docs.

## Picking Agent Toolkit vs MCP Server

**Agent Toolkit** when:
- Building a custom agent in code (TS/Python)
- Embedding PayPal capabilities into your own product
- Need fine-grained control via `configuration.actions`
- Backend services, webhook-driven flows, framework-native agents

**MCP Server** (remote) when:
- AI experience lives inside an MCP client (Claude Desktop, Cursor)
- Want zero-code setup
- Want the commerce/cart tools (gift cards)

**MCP Server** (local) when:
- Need credentials to stay on the user's machine
- Want offline-from-PayPal-but-on-LAN setup

Not mutually exclusive — the toolkit's `/modelcontextprotocol` subpath and the standalone `@paypal/mcp` package come from the same source.

## Auth notes

PayPal's official examples use these env var names (convention, not formally codified):
- `PAYPAL_CLIENT_ID`
- `PAYPAL_CLIENT_SECRET` (TS) / `PAYPAL_SECRET` (Python)
- `PAYPAL_ACCESS_TOKEN` (MCP server local mode)
- `PAYPAL_ENVIRONMENT` (`SANDBOX` or `PRODUCTION`)

For sandbox in toolkit: `Configuration(context=Context(sandbox=True))` (Python) or `configuration: { context: { sandbox: true } }` (TS).

## Common pitfalls

- **Sandbox vs production credentials** — same as the rest of the PayPal stack; they're separate.
- **Access token expiry on local MCP** — tokens last ~8h. If the user's MCP suddenly fails, the token expired.
- **Tool name pluralization mismatch** — see catalog notes above.
- **TypeScript-only / Python-only stale docs** — the GitHub root README claims TS-only but Python clearly exists. Trust docs.paypal.ai.
- **Asking the LLM to do too much in one tool call** — break complex flows into multiple tool invocations. The toolkit doesn't bundle multi-step workflows (except the `PayPalWorkflows` helper in TS Vercel AI SDK).

## Reference URLs

- AI tools landing: https://docs.paypal.ai/
- Agent Toolkit quickstart: https://docs.paypal.ai/developer/tools/ai/agent-toolkit-quickstart
- MCP quickstart: https://docs.paypal.ai/developer/tools/ai/mcp-quickstart
- Agent tools reference: https://docs.paypal.ai/developer/tools/ai/agent-tools-ref
- LLM integration: https://docs.paypal.ai/developer/tools/ai/llm-integration
- Prompt best practices: https://docs.paypal.ai/developer/tools/ai/prompt-best-practices
- Apps + scopes + credentials: https://docs.paypal.ai/developer/how-to/apps-scopes-credentials
- GitHub: https://github.com/paypal/agent-toolkit
