@extends('student.manual.layout')
@section('title', 'Chapter 11: Financial Reports')
@section('template')
  <section id="chapter-11-financial-reports-statement-analysis">
    <h1>CHAPTER 11: FINANCIAL REPORTS &amp; STATEMENT ANALYSIS</h1>
    <div class="learning-objectives">
      <table class="doc-table">
        <tr>
          <td>
            <h2>Learning Objectives:</h2>
            <p>By the end of this chapter, you will be able to:</p>
            <ul>
              <li>Read and interpret Income Statements, Balance Sheets, and Cash Flow Statements</li>
              <li>Calculate and analyze key financial ratios, including profitability, efficiency, and liquidity
              </li>
              <li>Trace operational problems from financial statement symptoms to their root causes</li>
              <li>Use financial analysis to guide strategic and tactical decisions for the next quarter</li>
              <li>Understand how supply chain choices create or destroy shareholder value</li>
            </ul>
          </td>
        </tr>
      </table>
    </div>

    <h2 id="ch11-why-financial-literacy-matters">Why Financial Literacy Matters</h2>
    <p>The dashboard shows you what happened—revenue went up, CSI went down, market share shifted. But the
      dashboard doesn't tell you WHY these changes occurred or where to intervene. That's what financial
      statements do.</p>
    <p>Your three core financial statements provide complementary perspectives on business performance. The Income
      Statement (Profit &amp; Loss) reveals where money went and which activities generated profit. The Balance
      Sheet shows what you own, what you owe, and your overall operational health at a specific point in time. The
      Cash Flow Statement explains why cash might be tight even when the business appears profitable on paper.</p>
    <p>Your three core financial statements are:</p>
    <ul>
      <li>Income Statement (P&amp;L): Where did our money go? Which activities are profitable?</li>
      <li>Balance Sheet: What do we own, what do we owe, and what's our operational health?</li>
      <li>Cash Flow Statement: Why is cash tight even though we're profitable?</li>
    </ul>
    <h2 id="ch11-part-1-the-income-statement">Part 1: The Income Statement</h2>
    <p>Every operational decision you make in Chapters 3-10 ultimately flows through to your Income Statement,
      affecting either revenue or expenses</p>
    <p>REVENUE</p>
    <ul>
      <li>Smart Home Assistant (SHA) Sales</li>
      <li>Smart Thermostat (ST) Sales</li>
    </ul>
    <p>TOTAL REVENUE</p>
    <p>COST OF GOODS SOLD (COGS)</p>
    <ul>
      <li>Raw Materials (plastics, casings)</li>
      <li>Sub-Assemblies (Audio Modules, Control Interfaces, Motherboards)</li>
      <li>Direct Labor (production wages)</li>
      <li>Manufacturing Overhead</li>
      <li>Emergency Production Premiums</li>
      <li>Warranty Costs</li>
      <li>Packaging Costs</li>
      <li>Defect-Related Costs</li>
    </ul>
    <p>TOTAL COGS</p>
    <p>GROSS PROFIT</p>
    <p>Gross Margin % = (Gross Profit ÷ Revenue) × 100</p>
    <p>OPERATING EXPENSES</p>
    <ul>
      <li>Administrative Overhead (base + forecast penalty)</li>
      <li>Transportation Costs</li>
      <li>Distribution Center Costs</li>
      <li>Service Outsourcing</li>
      <li>Marketing &amp; Advertising</li>
      <li>Product Introductions</li>
      <li>Price Change Penalties</li>
      <li>RFID Costs</li>
      <li>IT Synchronization Costs</li>
      <li>Inventory Holding Charges</li>
    </ul>
    <p>TOTAL OPERATING EXPENSES</p>
    <p>OPERATING INCOME</p>
    <p>Operating Margin % = (Operating Income ÷ Revenue) × 100</p>
    <p>Interest Expense (if loans outstanding)</p>
    <p>Taxes</p>
    <p>NET INCOME</p>
    <p>Net Profit Margin % = (Net Income ÷ Revenue) × 100</p>
    <p>Understanding Gross Margin</p>
    <p>Gross Margin % = [(Revenue - COGS) ÷ Revenue] × 100</p>
    <p>This is your first profitability checkpoint. It tells you: "Before we pay for any overhead, marketing, or
      transportation, how much do we keep per dollar of sales?"</p>
    <p>Typical ranges in consumer electronics: 30-50%</p>
    <p>If your Gross Margin is shrinking: If your Gross Margin is shrinking, five diagnostic questions reveal
      common causes. First, determine whether you have switched to more expensive suppliers or lost volume
      discount tiers, either of which increases material costs. Second, check whether emergency procurement has
      triggered, adding 75-100% premiums to component costs. Third, examine whether your Defect Rate has
      increased, forcing expensive replacements and warranty fulfillment. Fourth, verify you are hitting volume
      discount tiers for plastics and casings—missing the 1 million kilogram threshold costs $600,000+ per
      quarter. Finally, assess whether overproduction followed by inventory disposal is destroying value through
      holding charges and write-offs.</p>
    <h2 id="ch11-part-2-the-balance-sheet">Part 2: The Balance Sheet</h2>
    <p>Capital investments in distribution centers (Chapter 5) and RFID equipment (Chapter 5) appear as assets on
      your Balance Sheet, while inventory from procurement (Chapter 3) and production (Chapter 4) affects both
      assets and working capital.</p>
    <p>ASSETS</p>
    <p>Current Assets:</p>
    <ul>
      <li>Cash &amp; Cash Equivalents</li>
      <li>Accounts Receivable</li>
      <li>Inventory:</li>
      <li>Raw Materials</li>
      <li>Work in Process (shells)</li>
      <li>Finished Goods Inventory</li>
      <li>On-Order Materials (MB with 1-quarter lag)</li>
    </ul>
    <p>Fixed Assets:</p>
    <ul>
      <li>Property, Plant &amp; Equipment:</li>
      <li>Distribution Centers (net of depreciation)</li>
      <li>RFID Equipment (if insourced)</li>
    </ul>
    <p>TOTAL ASSETS</p>
    <p>LIABILITIES &amp; EQUITY</p>
    <p>Current Liabilities:</p>
    <ul>
      <li>Accounts Payable</li>
      <li>Short-Term Loans</li>
      <li>Warranty Reserves</li>
    </ul>
    <p>Long-Term Liabilities:</p>
    <ul>
      <li>Long-Term Debt (if any)</li>
    </ul>
    <p>TOTAL LIABILITIES</p>
    <p>Shareholders' Equity:</p>
    <ul>
      <li>Common Stock</li>
      <li>Retained Earnings</li>
    </ul>
    <p>TOTAL EQUITY</p>
    <p>TOTAL LIABILITIES + EQUITY = TOTAL ASSETS</p>
    <p>Key Balance Sheet Ratios</p>
    <p>LIQUIDITY RATIOS (Can you pay your bills?)</p>
    <p>Current Ratio = Current Assets ÷ Current Liabilities</p>
    <ul>
      <li>Healthy range: 1.5 - 3.0</li>
      <li>Below 1.0 = danger (can not cover liabilities)</li>
      <li>Above 3.0 = inefficient (too much cash/inventory idle)</li>
    </ul>
    <p>Quick Ratio = (Current Assets - Inventory) ÷ Current Liabilities</p>
    <ul>
      <li>Healthy range: 1.0+</li>
      <li>Can you pay bills without selling inventory?</li>
    </ul>
    <p>EFFICIENCY RATIOS (How well are you using assets?)</p>
    <p>Inventory Turnover = COGS ÷ Average Inventory</p>
    <ul>
      <li>Healthy range: 4-8+ (depends on industry)</li>
      <li>Low turnover = inventory sitting too long</li>
      <li>High turnover = lean operation</li>
    </ul>
    <p>Asset Turnover = Revenue ÷ Total Assets</p>
    <ul>
      <li>How much revenue per dollar of assets?</li>
      <li>Higher = better (efficient use of assets)</li>
    </ul>
    <h2 id="ch11-part-3-the-cash-flow-statement">Part 3: The Cash Flow Statement</h2>
    <p>What the Cash Flow Statement Shows</p>
    <p>The Cash Flow Statement answers: "We made a profit—so where did all the cash go?"</p>
    <table class="doc-table">
      <tr>
        <th>CRITICAL CONCEPT: Profit ≠ Cash
          You can be profitable and still run out of cash due to timing differences between accounting and cash
          movements. Revenue is recorded when earned at the point of sale, but cash arrives later when customers
          actually pay, creating a delay between reported profit and cash receipt. Capital expenditures like
          distribution center purchases drain cash immediately as large one-time outlays, but accounting spreads
          the expense over time through depreciation rather than recognizing it all at once. Inventory ties up
          cash when you purchase materials and build products, yet these costs don't hit the Profit &amp; Loss
          statement until the products actually sell, potentially quarters later. These timing mismatches explain
          how a profitable company on paper can face serious cash constraints.</th>
      </tr>
    </table>
    <p>The FLEXEE Cash Flow Statement Structure</p>
    <p>CASH FLOW FROM OPERATING ACTIVITIES</p>
    <ul>
      <li>Net Income (from P&amp;L)</li>
      <li>Adjustments for non-cash items:</li>
      <li>+ Depreciation &amp; Amortization</li>
      <li>+ Losses (e.g., inventory disposal)</li>
      <li>Changes in working capital:</li>
      <li>- Increase in Inventory (cash used to buy/build)</li>
      <li>+ Decrease in Inventory (sold inventory, freed cash)</li>
      <li>+ Increase in Accounts Payable (delayed payment)</li>
    </ul>
    <p>CASH FROM OPERATING ACTIVITIES</p>
    <p>CASH FLOW FROM INVESTING ACTIVITIES</p>
    <ul>
      <li>- Purchase of PP&amp;E (DCs, equipment)</li>
      <li>+ Sale of PP&amp;E (if you close/sell assets)</li>
    </ul>
    <p>CASH FROM INVESTING ACTIVITIES</p>
    <p>CASH FLOW FROM FINANCING ACTIVITIES</p>
    <ul>
      <li>+ Proceeds from loans</li>
      <li>- Repayment of loans</li>
      <li>- Dividends paid (if any)</li>
    </ul>
    <p>CASH FROM FINANCING ACTIVITIES</p>
    <p>NET CHANGE IN CASH</p>
    <h2 id="ch11-chapter-summary-key-takeaways">Chapter Summary: Key Takeaways</h2>
    <ul>
      <li>Financial statements translate operational decisions into financial performance: Every supply chain
        choice—from procurement to service levels—flows through the Income Statement, Balance Sheet, and Cash Flow
        Statement.</li>
      <li>The Income Statement shows profitability drivers: Revenue minus COGS gives Gross Profit, then
        subtracting Operating Expenses (SG&amp;A) yields Operating Income, and finally Net Income after interest
        and taxes. Understanding this cascade reveals margin improvement opportunities.</li>
      <li>The Balance Sheet reflects your asset efficiency: Current Assets (cash, inventory, receivables) and
        Fixed Assets (plants, DCs) show how capital is deployed. Inventory turnover and asset utilization directly
        impact return on assets (ROA).</li>
      <li>Cash Flow differs from profit: Positive Net Income doesn't guarantee positive cash flow. Inventory
        buildup, capital investments, and timing of receivables/payables create cash flow challenges even for
        profitable companies.</li>
      <li>Working capital management is critical: The cash conversion cycle—how long capital is tied up in
        inventory and receivables before customer payments arrive—determines liquidity and financial flexibility.
      </li>
      <li>Financial ratios enable performance benchmarking: Gross Margin %, Operating Margin %, ROA, ROE,
        Inventory Turnover, and Current Ratio provide comparable metrics to assess your performance against
        competitors and identify improvement areas.</li>
      <li>Supply chain decisions have financial trade-offs: Premium service increases SG&amp;A but boosts CSI and
        revenue. Owned DCs require capital investment but reduce per-unit costs. Inventory reduces stockouts but
        ties up cash. Financial reports quantify these trade-offs and guide strategic choices.</li>
    </ul>
  </section>
@endsection
