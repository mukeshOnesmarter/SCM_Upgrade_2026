@extends('student.manual.layout')
@section('title', 'Chapter 8: Demand Management Decisions')
@section('template')
  <section id="chapter-8-demand-management">
    <h1>CHAPTER 8: DEMAND MANAGEMENT</h1>
    <div class="learning-objectives">
      <table class="doc-table">
        <tr>
          <td>
            <h2>Learning Objectives</h2>
            <p>By the end of this chapter, you will be able to:</p>
            <ul>
              <li>Enable or disable product sales by region and channel</li>
              <li>Understand how pricing decisions affect revenue and penalties</li>
              <li>Control marketing spend as an operating expense</li>
              <li>Distinguish between demand availability and demand fulfillment</li>
              <li>Align demand decisions with supply, cost, and profitability</li>
              <li>Interpret how demand decisions flow into Product and Corporate P&amp;L</li>
            </ul>
          </td>
        </tr>
      </table>
    </div>

    <h2 id="ch8-demand-management-as-revenue-engine">Demand Management As Revenue Engine</h2>
    <p>While supply-side decisions (Chapters 3-7) determine what you CAN deliver, demand-side decisions determine
      what customers WANT to buy.</p>
    <p>Demand management includes:</p>
    <ul>
      <li>INTRODUCTIONS: Launching products in regions/channels</li>
      <li>PRICING: Setting wholesale (Retail) or final (Direct) prices</li>
      <li>MARKETING: Advertising spend to drive awareness and demand</li>
      <li>DROPS: Exiting products from channels/regions</li>
    </ul>
    <p>These decisions are powerful but expensive. A product launch costs $100k-$300k. A price change costs $10k+
      plus revenue percentage. Marketing has diminishing returns.</p>
    <p>This chapter teaches you to invest in demand strategically maximizing revenue while controlling costs.</p>
    <table class="doc-table">
      <tr>
        <th>Real-World Context: Demand Management Examples</th>
        <th>Real-World Context: Demand Management Examples</th>
        <th>Real-World Context: Demand Management Examples</th>
      </tr>
      <tr>
        <td>APPLE iPHONE LAUNCHES</td>
        <td>XIAOMI</td>
        <td>TESLA MODEL 3</td>
      </tr>
      <tr>
        <td>Marketing: $100M+ per launch (global campaigns)
          Pricing: Premium ($999-1,299) despite $400-500 COGS
          Channel: Direct (Apple Stores) + Retail (carriers)
          Result: 200M+ units/year, 40% gross margins</td>
        <td>Marketing: Minimal (&lt;$50M/year, relies on social media)
          Pricing: Cost-plus ($299 for similar specs to iPhone)
          Channel: Direct online only (no retail)
          Result: 150M+ units/year, 15% gross margins, high volume</td>
        <td>Launch cost: $500M+ (includes production setup)
          Pricing: Started $35k, raised to $45k as demand proved strong
          Marketing: $0 (100% word-of-mouth, Elon's Twitter)
          Result: 1M+ units/year</td>
      </tr>
      <tr>
        <td>In FLEXEE: You will make similar choices – launch big or start small? Price high or low? Invest in
          marketing or rely on organic growth?</td>
        <td>In FLEXEE: You will make similar choices – launch big or start small? Price high or low? Invest in
          marketing or rely on organic growth?</td>
        <td>In FLEXEE: You will make similar choices – launch big or start small? Price high or low? Invest in
          marketing or rely on organic growth?</td>
      </tr>
    </table>
    <h2 id="ch8-part-1-product-introductions-drops">Part 1: Product Introductions &amp; Drops</h2>
    <p>Before selling products in any channel or region, you must formally introduce them, incurring upfront
      marketing and distribution setup costs. Retail channel introductions cost significantly more than direct
      channel due to retailer relationship establishment, shelf space negotiations, and retail-specific marketing
      materials. The simulation limits you to three product changes per quarter—including both introductions and
      drops—to reflect realistic capacity constraints in marketing and sales operations.</p>
    <h3 id="ch8-introduction-costs">Introduction Costs</h3>
    <table class="doc-table">
      <tr>
        <th>Action</th>
        <th>Cost</th>
        <th>Notes</th>
      </tr>
      <tr>
        <td>Introduce to Retail (C1)</td>
        <td>$300k per product</td>
        <td>Counts toward 3-change limit</td>
      </tr>
      <tr>
        <td>Introduce to Direct (C2)</td>
        <td>$100k per product</td>
        <td>Counts toward 3-change limit</td>
      </tr>
      <tr>
        <td>Drop from Retail (C1)</td>
        <td>$0</td>
        <td>Counts toward 3-change limit</td>
      </tr>
      <tr>
        <td>Drop from Direct (C2)</td>
        <td>$0</td>
        <td>Counts toward 3-change limit</td>
      </tr>
      <tr>
        <td colspan="3"><strong>Maximum Combined Changes:</strong> 3 introductions + drops per quarter</td>
      </tr>
    </table>
    <p>Examples:</p>
    <p>• Launch SHA in R2 Retail: $300k
      • Launch ST in R3 Direct: $100k
      • Launch SHA in R1 Retail + R1 Direct: $300k + $100k = $400k</p>
    <p><strong>Change Limit Example:</strong> In Q2, if you introduce SHA in R2 Retail ($300k), introduce ST in R1
      Direct ($100k), and drop SHA from R3 Retail ($0), you have used all 3 allowed changes for the quarter. You
      cannot make additional introductions or drops in Q2, even though one action was costless. The limit reflects
      operational capacity constraints in your marketing and sales teams, regardless of whether changes involve
      adding or removing products.</p>
    <h3 id="ch8-introduction-strategies">Introduction Strategies</h3>
    <p>Three distinct launch strategies offer different risk-return profiles. An aggressive launch strategy
      introduces products across all regions and channels immediately in Quarter 1, potentially launching six
      product-region-channel combinations at costs ranging from $600,000 to $1,800,000. This approach captures
      market share early and tests all options simultaneously but creates massive cash drain with uncertain demand
      and high risk if forecasts prove optimistic.</p>
    <p>A conservative launch strategy begins with SHA in R1 Retail only during Q1, costing $300,000 to test the
      home market before expansion. Quarter 2 adds ST in R1 Retail ($300,000) and SHA in R1 Direct ($100,000),
      bringing total Q1-Q2 investment to $700,000. This approach minimizes risk by learning from the home region
      before committing to distant markets, though competitors may capture other regions first while you proceed
      cautiously.</p>
    <p>A phased launch strategy balances risk and opportunity by spreading introductions across four quarters. Q1
      launches SHA R1 Retail ($300,000). Q2 adds SHA R2 Retail ($300,000) and ST R1 Retail ($300,000). Q3
      introduces SHA R1 Direct ($100,000) and SHA R3 Retail ($300,000). Q4 completes the rollout with ST R2 Retail
      ($300,000). Total investment of $1,600,000 spreads over four quarters, balancing cash flow with data-driven
      expansion based on Q1-Q2 performance. This approach represents the recommended strategy for most situations:
      launch R1 first as your lowest-cost test market, then expand based on actual results rather than forecasts.
    </p>
    <p>Introduction Breakeven Analysis</p>
    <p>Retail channel introductions require careful breakeven analysis due to their $300,000 investment. Consider
      the volume required to recover this cost through gross profit. Assuming a wholesale price of $150, cost of
      goods sold of $92 per SHA unit, and resulting in gross profit of $58 per unit, the breakeven calculation is
      straightforward: divide $300,000 by $58 to get 5,172 units. If a region generates 5,172 or more units over
      the product's lifetime, the retail launch investment pays for itself.</p>
    <p>Apply this framework to a specific scenario. Suppose R2 SHA forecasts show 15,000 units in Q2, 18,000 in
      Q3, and 20,000 in Q4. The Q2 launch reaches breakeven volume within Q2 alone, making it an obvious decision
      to proceed. The strong initial quarter volume, combined with growth trajectory, justifies the $300,000
      investment immediately.</p>
    <p>Direct channel introductions carry lower risk due to their $100,000 cost. Using the same $58 gross profit
      per unit, breakeven occurs at only 1,724 units ($100,000 ÷ $58). This one-third cost relative to retail
      means faster payback and lower volume requirements, making direct channel launches appropriate even for
      smaller markets or test scenarios.</p>
    <h3 id="ch8-dropping-products">Dropping Products</h3>
    <p>To exit a channel or region, set the product to Active = No and Marketing = $0. The simulation charges no
      exit penalty, making drops costless from a direct expense perspective. However, timing matters
      strategically. Drop products when regional volume falls below 3,000 units per quarter, as this low volume
      fails to cover the introduction cost over reasonable timeframes. CSI damage in a specific region also
      justifies dropping that product-region combination, as it's better to exit than harm your brand. Strategic
      refocus provides another valid reason—concentrating resources on fewer, higher-performing regions often
      outperforms thin spreading across many marginal markets.</p>
    <p>Exercise caution before dropping products, as re-introduction requires paying the full $300,000 or $100,000
      cost again. Once you exit a market, returning is expensive. Give products 3-4 quarters to establish
      themselves before concluding they are failing, unless performance is clearly catastrophic. Premature drops
      waste the initial investment and forfeit market position that may have recovered with patience.</p>
    <h2 id="ch8-part-2-channel-selection-retail-vs-direct">Part 2: Channel Selection (Retail vs. Direct)</h2>
    <h3 id="ch8-channel-economics-comparison">Channel Economics Comparison</h3>
    <p>The two sales channels offer fundamentally different economics that shape your strategic choices. Retail
      channel economics begin with a wholesale price you set—for example, $150 per unit that you receive.
      Retailers then mark up this price approximately 30% to reach consumers at around $200. Your costs per retail
      unit include $92 in COGS, $4 for order fulfillment (bulk shipments to retailers), $12 for RFID tagging (or
      $1 if insourced), and approximately $7.50 in average transportation costs with a distribution center. This
      structure yields a margin of $34.50 per unit, or 23% of your wholesale price. See Chapter 5 for RFID
      breakeven analysis showing when to switch from outsourcing to insourcing.</p>
    <p>Direct channel economics offer higher margins but different cost structures. You control the final consumer
      price—for instance, $200 that you receive directly. Your costs include the same $92 COGS, but order
      fulfillment jumps to $24 per unit due to individual consumer shipments rather than bulk deliveries. RFID
      tagging is unnecessary, saving $12 per unit. Transportation averages $15 per unit with a distribution
      center. The resulting margin is $69 per unit, representing 35% of your revenue—substantially higher than the
      23% retail margin.</p>
    <p>The strategic trade-off becomes clear through this comparison. Direct channel delivers $69 margin per unit
      (35%) while retail provides $34.50 margin (23%). However, retail channels typically generate three to five
      times the volume of direct channels due to retailer reach and established customer relationships. Most
      successful firms pursue an omnichannel strategy, using retail for volume and direct for margin, serving
      different customer segments through each channel.</p>
    <h3 id="ch8-channel-strategy-by-positioning">Channel Strategy By Positioning</h3>
    <p>Your competitive positioning should drive channel strategy choices. Cost leadership strategies focus
      primarily on the retail channel as the volume driver, moving quickly to insource RFID once volume exceeds
      35,000 units to minimize variable costs. Pricing in retail channels should be aggressive—$120-140
      wholesale—to maximize volume throughput. This approach generates 25-30% margins on high volume, accumulating
      profit through scale rather than premium pricing.</p>
    <p>Differentiation strategies prioritize the direct channel to maintain control over customer experience and
      capture premium pricing. Set direct prices at $200-250 to signal quality and fund superior service levels.
      This approach yields 35-40% margins on lower volume, with retail added later if scale becomes necessary. The
      direct channel allows brand control and customer relationship ownership that retail partnerships dilute.</p>
    <p>Balanced strategies launch retail first to establish volume baseline, then add direct channels in Q2-Q3 to
      capture margin opportunities. Price retail at $140-160 wholesale and direct at $180-220, creating price
      differentiation that segments customers by channel preference. This approach generates blended margins of
      25-35% while diversifying revenue streams and reducing dependence on any single channel or customer type.
    </p>
    <h2 id="ch8-part-3-pricing-strategy">Part 3: Pricing Strategy</h2>
    <h3 id="ch8-price-change-costs">Price Change Costs</h3>
    <p>Price changes incur substantial penalties that discourage frequent adjustments. The cost structure includes
      three components: a $10,000 base charge, plus $200 for every dollar of price change, plus 0.25% of current
      quarter revenue. Consider a scenario where quarterly revenue runs $100 million and you raise wholesale price
      from $150 to $155. The base charge costs $10,000. The per-dollar charge adds $1,000 ($200 × 5 dollars). The
      revenue percentage contributes $250,000 (0.0025 × $100M). Total cost: $261,000 for a $5 price increase. This
      steep penalty structure intentionally discourages reactive price changes and rewards strategic pricing
      discipline. Frequent price changes also increase administrative overhead, which affects your Operating
      Margin---see Chapter 11 for how price change costs appear on your Income Statement.</p>
    <h3 id="ch8-pricing-decision-framework">Pricing Decision Framework</h3>
    <p>Three approaches guide initial Q1 pricing decisions, each with different strategic implications. Cost-plus
      methodology starts with your COGS of $92 and applies a target margin—typically 30%—to calculate price.
      Dividing $92 by 0.70 (to leave 30% margin) yields approximately $132. This method ensures profitability but
      ignores competitive dynamics and customer value perception.</p>
    <p>Competitive pricing examines rival prices ranging from $150-180 and positions relative to competition.
      Price below competitors at $140 for cost leadership positioning, match competition at $160 for parity, or
      price above at $180 for premium positioning. This approach recognizes market context but may leave money on
      the table if your product offers superior value.</p>
    <p>Value-based pricing starts with customer willingness-to-pay—perhaps $200 based on market research—and
      prices slightly below at $180 to capture value while leaving cushion for customer surplus. This method
      maximizes revenue capture but requires strong understanding of customer perceptions and differentiation from
      alternatives. The recommended approach combines these methods: start moderately at $150-160, observe demand
      elasticity in Q1-Q2, then adjust based on fill rates and market response.</p>
    <h2 id="ch8-part-4-price-adjustment-strategy">Part 4: Price Adjustment Strategy</h2>
    <h3 id="ch8-when-to-raise-prices/when-to-lower-prices">When To Raise Prices/When to Lower Prices</h3>
    <p>Price increases make strategic sense under two conditions. When demand exceeds supply—evidenced by fill
      rates below 95% as you sell everything produced—and CSI remains strong despite occasional stockouts,
      customers signal willingness to pay more. Raise prices $5-10 to capture this revealed value. Premium
      positioning also justifies price increases when CSI exceeds 90, indicating strong quality perception, and
      competitors price at $180 while you charge only $150. Raising to $170 aligns pricing with your demonstrated
      quality advantage.</p>
    <p>Price decreases become necessary when market conditions weaken. Demand falling below forecast creates
      excess inventory and 100% fill rates, indicating insufficient customer interest at current prices. Lower
      prices $5-10 to stimulate demand and move inventory. Competitive pressure also forces price response: if a
      competitor drops to $130 and you are losing market share at $150, match at $130 or go slightly below to $135
      to remain competitive.</p>
    <h3 id="ch8-price-change-breakeven">Price Change Breakeven</h3>
    <p>Price change economics require multi-quarter thinking due to the substantial upfront penalty. Consider
      raising price from $150 to $155, which costs $261,000 as calculated earlier. At 50,000 units per quarter,
      the revenue increase is $250,000 ($5 × 50,000 units). With 55% gross margin, this generates $137,500 in
      incremental gross profit. Against the $261,000 one-time cost, Quarter 1 shows a $123,500 loss. The price
      increase destroys value in the short term.</p>
    <p>However, the cost is one-time while the benefit recurs. Over four quarters, revenue gains total $1,000,000
      ($250,000 × 4 quarters). Gross profit accumulates to $550,000 ($1M × 55%). Subtracting the one-time $261,000
      cost yields $289,000 net gain over one year. The investment pays back in approximately two quarters, making
      it worthwhile for strategic price positioning. The lesson: evaluate price changes over multiple quarters,
      not single periods. Immediate losses often precede sustained gains.</p>
    <h3 id="ch8-pricing-mistakes-to-avoid">Pricing Mistakes To Avoid</h3>
    <p>MISTAKE 1: Frequent Price Changes
      Fix: Penalty is steep. Change once per 2-3 quarters maximum.</p>
    <p>MISTAKE 2: Small Changes (&lt;$5)
      Fix: If penalty is $200k+, a $2 change doesn't move enough revenue. Make meaningful changes ($5-10).</p>
    <p>MISTAKE 3: Ignoring Revenue-Based Penalty
      Fix: 0.25% of revenue can be $100k-300k. Factor this into calculations.</p>
    <p>MISTAKE 4: Raising Prices When CSI Is Low
      Fix: Customers won't pay more for poor quality. Fix CSI first, then raise prices.</p>
    <h2 id="ch8-part-5-marketing-strategy">Part 5: Marketing Strategy</h2>
    <h3 id="ch8-marketing-budget-allocation-&amp;-rules">Marketing Budget Allocation &amp; Rules</h3>
    <p>Marketing budgets are set individually for each product-region-channel combination. For example, SHA in
      Region 1 Retail has its own separate marketing budget, distinct from SHA in Region 1 Direct or SHA in Region
      2 Retail. When you introduce a product in any channel, that channel starts with a marketing budget of
      $100,000. Corporate policy limits how much you can adjust these budgets: any channel's marketing spending
      must remain within ±$100,000 of its initial $100,000 value, meaning the allowable range is $0 to $200,000
      for any channel. The FLEXEE system will automatically adjust values that exceed these limits. Marketing
      spending raises demand with diminishing returns—the first $50,000 produces stronger effects than the next
      $50,000. Spillover effects generate synergies: retail marketing spending creates 30-50% of its impact in the
      direct channel within the same region, as brand awareness built through retail advertising benefits all
      channels.</p>
    <p>Marketing Budget Sizing</p>
    <p>Initial marketing budgets for new launches should range from $50,000-100,000 per channel, providing
      sufficient visibility without overspending before demand patterns emerge. Established products warrant
      $80,000-120,000 per channel based on proven response rates. The diminishing returns curve follows a
      predictable pattern: spending from $0 to $50,000 generates high impact with 20-30% demand increases. Raising
      budgets from $50,000 to $100,000 produces moderate 10-15% incremental gains. Further increases from $100,000
      to $150,000 yield low 5-8% returns. Beyond $150,000, minimal 2-4% improvements make additional spending
      inefficient.</p>
    <p>The recommended test approach allocates $80,000 per channel as Q1 baseline, increases spending to $120,000
      in high-potential regions during Q2 to measure elasticity, adjusts Q3 budgets based on observed Q2 response
      rates, and optimizes Q4 allocation by shifting budgets toward responsive channels while reducing spending on
      saturated ones.</p>
    <h3 id="ch8-marketing-roi-calculation">Marketing ROI Calculation</h3>
    <p>Marketing ROI calculations demonstrate the value and limits of advertising investment through a specific R2
      SHA retail example. Quarter 1 establishes the baseline without marketing: 10,000 units of demand generate
      $1.5M revenue (10,000 × $150 wholesale price), producing $600,000 gross profit at 40% margin. Quarter 2
      introduces $100,000 marketing spend, lifting demand 30% to 13,000 units. Revenue increases to $1.95M,
      generating $780,000 gross profit. After subtracting the $100,000 marketing cost, net gain reaches $80,000
      over baseline ($780k - $600k - $100k). ROI calculates to 80% ($80k ÷ $100k)—a strong return justifying the
      investment. Marketing spending affects both your top-line Revenue and your Operating Margin---see Chapter 11
      for how to evaluate marketing ROI using your Income Statement.</p>
    <p>Quarter 3 tests higher spending at $150,000 marketing budget. Demand rises to 14,500 units, representing
      45% total lift but only 15% incremental over Q2. Revenue reaches $2.175M with $870,000 gross profit. Net
      gain totals $120,000 ($870k - $600k baseline - $150k marketing), maintaining 80% ROI. However, the
      incremental analysis reveals diminishing returns: the additional $50,000 marketing (Q2 to Q3) generated
      $90,000 incremental profit ($870k - $780k), yielding 180% incremental ROI—excellent efficiency. Yet the
      demand lift slowed from 30% to 45% total (+15% incremental), signaling approaching saturation. Further
      spending increases would likely drop below 50% ROI.</p>
    <h3 id="ch8-marketing-strategy-by-positioning">Marketing Strategy By Positioning</h3>
    <p>Marketing strategy should align with overall competitive positioning. Cost leadership strategies minimize
      marketing spending at $50-80,000 per channel, relying on low prices rather than advertising to drive volume.
      Focus spending on the retail channel as the mass-market driver. This lean approach accepts lower awareness
      in exchange for superior cost structure and price competitiveness.</p>
    <p>Differentiation strategies invest heavily in marketing at $100-150,000 per channel to build brand awareness
      and justify premium pricing. Concentrate on direct channels plus premium retail outlets to reach
      quality-conscious customers. High marketing investment supports the brand perception necessary to command
      $200-250 prices. Build strong brand equity even if it compresses short-term margins.</p>
    <p>Growth and land-grab strategies maximize marketing at $120-150,000 per channel, accepting negative
      short-term ROI to capture market share. Outspend competitors across all channels and regions during the
      land-grab phase. Once market position is established, scale back to $80-100,000 per channel and harvest the
      customer base built during aggressive expansion.</p>
    <h2 id="ch8-part-6-integrated-demand-management">Part 6: Integrated Demand Management</h2>
    <h3 id="ch8-introductions-pricing-and-marketing-must-work-together">Introductions, Pricing, And Marketing Must
      Work Together</h3>
    <p>Integrated Strategy Example: Premium Positioning</p>
    <p>Premium positioning requires coordinated introduction, pricing, and marketing decisions working in concert.
      The Q1 launch introduces SHA in R1 Direct for $100,000, sets pricing at $200 to signal quality, and
      allocates $120,000 marketing to build awareness among premium customers. Total Q1 investment: $220,000.
      Quarter 1 results generate 8,000 unit sales—lower volume typical of premium positioning. Revenue reaches
      $1.6M (8,000 × $200), producing $560,000 gross profit at 35% margin. Subtracting the $220,000 introduction
      and marketing investment yields $340,000 net profit in the launch quarter. Quarter 2 expands by adding R1
      Retail at $300,000 introduction cost, pricing retail at $180 (slightly below direct to maintain channel
      distinction), and maintaining marketing support with $100,000 for retail and $100,000 for direct channels.
      Total Q2 investment: $500,000. Quarters 2-4 combined generate substantial returns: direct channel produces
      24,000 units over three quarters (8,000 per quarter) at $200, creating $4.8M revenue. Retail adds 45,000
      units over three quarters (15,000 per quarter) at $180, contributing $8.1M. Total revenue reaches $12.9M
      with average 35% gross profit yielding $4.5M. After deducting all costs, $220,000 Q1, $500,000 Q2, and
      $600,000 in Q3-Q4 ongoing marketing—net profit totals $3.18M. The integrated strategy demonstrates how
      premium positioning, supported by appropriate pricing and marketing investment, generates strong returns
      despite lower volume.</p>
    <h3 id="ch8-common-demand-management-pitfalls">Common Demand Management Pitfalls</h3>
    <p>Five mistakes consistently undermine demand management effectiveness. Launching everywhere at once drains
      $1.5M+ in Q1 through untested product-market fit, creating cash crisis with uncertain demand and potentially
      inadequate supply. Phase launches from R1 to R2 to R3 to learn before committing capital. Frequent price
      changes accumulate penalties of $200,000+ per adjustment while creating customer and retailer confusion
      about positioning. Limit price changes to once per 2-3 quarters maximum.</p>
    <p>Overspending on marketing yields diminishing returns above $120,000 per channel, with ROI dropping below
      50% as saturation effects dominate. Cap spending at $100-120,000 per channel and test incrementally rather
      than making large commitments. Ignoring spillover effects causes budget waste: retail marketing generates
      30-50% impact in direct channel automatically, yet some managers allocate $150,000 to each. Recognize the
      spillover and allocate $100,000 retail with $80,000 direct to avoid redundancy.</p>
    <p>Finally, dropping products too quickly—exiting markets after just one or two weak quarters—wastes the
      $300,000 or $100,000 introduction investment and forfeits market position that might recover with patience.
      Give products 3-4 quarters to establish traction unless performance is clearly catastrophic.</p>
    <h2 id="ch8-chapter-summary-key-takeaways">Chapter Summary: Key Takeaways</h2>
    <ul>
      <li>Introductions Are Expensive But Necessary
        Retail: $300k. Direct: $100k. Breakeven at 1,724-5,172 units depending on margin.</li>
      <li>Phase Launches (R1 → R2 → R3)
        Avoid Q1 cash drain. Learn from home market first.</li>
      <li>Direct Has Higher Margins, Retail Has Higher Volume
        Direct: 35% margin. Retail: 23% margin. Both valuable for omnichannel.</li>
      <li>Price Changes Cost $200k+ (Including Revenue %)
        Make meaningful changes ($5-10), not frequent small tweaks.</li>
      <li>Marketing Has Diminishing Returns Above $100-120k
        Optimal spend: $80-120k. Test incrementally, don't overspend.</li>
      <li>Spillover Exists Between Channels (30-50%)
        Retail marketing helps Direct. Don't duplicate effort.</li>
      <li>Align Demand With Strategy
        Cost leadership: Low prices, minimal marketing, Retail focus.
        Differentiation: Premium prices, high marketing, Direct focus.</li>
      <li>Demand Decisions Are Long-Term Investments
        Price changes pay back in 2+ quarters. Introductions in 1-2 quarters. Evaluate over time.</li>
    </ul>
  </section>
@endsection
