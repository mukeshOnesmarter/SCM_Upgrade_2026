@extends('student.manual.layout')
@section('title', 'Chapter 3: Procurement Decisions')
@section('template')
  <section id="chapter-3-procurement-decisions">
    <h1>CHAPTER 3: PROCUREMENT DECISIONS</h1>
    <div class="learning-objectives">
      <table class="doc-table">
        <tr>
          <td>
            <h2>Learning Objectives:</h2>
            <p>By the end of this chapter, you will be able to:</p>
            <ul>
              <li>Evaluate suppliers based on total cost of ownership, including price, reliability, and failure
                rates</li>
              <li>Calculate optimal order quantities considering volume discounts and safety stock requirements
              </li>
              <li>Plan procurement timing, especially for long-lead-time components such as motherboards</li>
              <li>Understand emergency procurement mechanics and why they are costly</li>
              <li>Analyze supplier relationship costs and IT synchronization investments</li>
            </ul>
          </td>
        </tr>
      </table>
    </div>

    <h2 id="ch3-part-1-raw-materials-procurement-the-simple-part">Part 1: Raw Materials Procurement (The Simple
      Part)</h2>
    <p>Raw materials (Plastics and Casings) are straightforward to procure because:</p>
    <ul>
      <li>No lead time — order this quarter, receive this quarter</li>
      <li>No supplier choice — only one supplier for each</li>
      <li>No quality variability — materials are always perfect</li>
      <li>Only decision: How much to order based on volume discounts</li>
    </ul>
    <h3 id="ch3-plastics-procurement">Plastics Procurement</h3>
    <p>Plastics procurement begins at a base price of $4.00 per kilogram when no volume discounts apply. However,
      the pricing structure includes three discount tiers that reward larger orders. When procurement exceeds
      250,000 kilograms, an 8% discount applies, reducing the unit cost to $3.68 per kilogram and saving $0.32 per
      kilogram. At 500,000 kilograms, the discount increases to 14%, lowering costs to $3.44 per kilogram for a
      savings of $0.56 per kilogram. The maximum discount tier activates at 1,000,000 kilograms, providing a 19%
      reduction to $3.24 per kilogram and saving $0.76 per kilogram.</p>
    <p>Base Pricing:</p>
    <p>$4.00 per kg (no discount)</p>
    <p>Volume Discount Tiers:</p>
    <ul>
      <li>&gt;250,000 kg: -8% → $3.68/kg (saves $0.32/kg)</li>
      <li>&gt;500,000 kg: -14% → $3.44/kg (saves $0.56/kg)</li>
      <li>&gt;1,000,000 kg: -19% → $3.24/kg (saves $0.76/kg)</li>
    </ul>
    <p>Emergency Surcharge:</p>
    <p>+$1/kg (so emergency Plastics cost $5.00/kg)</p>
    <h3 id="ch3-casings-procurement">Casings Procurement</h3>
    <p>Casings follow an identical volume discount structure to plastics but with a higher base price of $5.00 per
      kilogram. The same three discount tiers apply: orders exceeding 250,000 kg receive an 8% discount ($4.60/kg,
      saving $0.40/kg), orders over 500,000 kg receive 14% off ($4.30/kg, saving $0.70/kg), and orders surpassing
      1,000,000 kg receive the maximum 19% discount ($4.05/kg, saving $0.95/kg). Emergency casings also carry the
      $1 per kilogram surcharge, making emergency procurement cost $6.00 per kilogram.</p>
    <p>Base Pricing:</p>
    <p>$5.00 per kg (no discount)</p>
    <p>Volume Discount Tiers:</p>
    <ul>
      <li>&gt;250,000 kg: -8% → $4.60/kg (saves $0.40/kg)</li>
      <li>&gt;500,000 kg: -14% → $4.30/kg (saves $0.70/kg)</li>
      <li>&gt;1,000,000 kg: -19% → $4.05/kg (saves $0.95/kg)</li>
    </ul>
    <h3 id="ch3-volume-discount-strategy">Volume Discount Strategy</h3>
    <p>The volume discount structure creates powerful incentives for strategic procurement planning. Consider a
      scenario where you are producing 100,000 total units (any combination of SHA and ST). Your plastics
      requirement totals 500,000 kilograms (100,000 units × 5 kg per unit). Without strategic planning, ordering
      at the base price costs $2,000,000. However, by reaching the second discount tier, the same 500,000
      kilograms costs only $1,720,000—a savings of $280,000, or 14% reduction. This single decision captures more
      profit than many operational improvements. These savings flow directly to your Operating Margin and Net
      Profit Margin—see Chapter 11 to understand how procurement decisions appear on your Income Statement.</p>
    <p>This discount structure drives two critical strategic imperatives. First, combine your SHA and ST orders
      rather than ordering separately by product. The discount tiers apply to total plastics or casings volume,
      not individual product lines. Splitting orders forfeits substantial discounts. Second, avoid fragmenting
      orders across multiple 'suppliers' for raw materials. Some students mistakenly order plastics from different
      sources for SHA versus ST, thinking it provides flexibility. In reality, this approach destroys value by
      preventing volume consolidation. The simulation treats plastics and casings as commodities with a single
      consolidated ordering decision, and your procurement strategy must recognize this structure to capture
      available savings.</p>
    <h2 id="ch3-part-2-sub-assembly-procurement-the-complex-part">Part 2: Sub-Assembly Procurement (The Complex
      Part)</h2>
    <p>Sub-assemblies (Audio Modules, Control Interfaces, Motherboards) are where procurement gets strategically
      interesting because:</p>
    <ul>
      <li>Suppliers differ on price, reliability, and quality</li>
      <li>Lead times matter (especially Motherboards)</li>
      <li>Failure rates directly affect production and CSI</li>
      <li>Emergency procurement is MUCH more expensive (+$4-8/unit)</li>
    </ul>
    <h3 id="ch3-sub-assembly-volume-discounts">Sub-Assembly Volume Discounts</h3>
    <p>Unlike raw materials with their tiered discount structure, sub-assemblies follow a simpler volume discount
      model that applies across all three component types: Motherboards, Audio Modules, and Control Interfaces.
      This unified discount structure creates strategic opportunities for procurement consolidation.</p>
    <p>DISCOUNT STRUCTURE:</p>
    <table class="doc-table">
      <tr>
        <th>Order Quantity (units)</th>
        <th>Discount</th>
        <th>Example: $20 Base Price</th>
      </tr>
      <tr>
        <td>Under 50,000</td>
        <td>Discount Not Applicable 0%</td>
        <td>$20.00 per unit</td>
      </tr>
      <tr>
        <td>Over 50,000</td>
        <td>Discount Applicable 10.4%</td>
        <td>$18.00 per unit</td>
      </tr>
      <tr>
        <td>Over 100,000</td>
        <td>Discount Applicable 17.5%</td>
        <td>$18.00 per unit</td>
      </tr>

    </table>
    <p>STRATEGIC IMPLICATIONS:</p>
    <p>The 50,000-unit threshold creates a critical procurement decision point. Consider a scenario where you're
      producing 45,000 SHA units requiring Audio Modules. At base prices (averaging $15 per Audio Module), your
      cost totals $675,000. By increasing your order to 50,001 units to trigger the 10% discount, your unit cost
      drops to $13.50, reducing total cost to $675,013.50 for the larger quantity—essentially acquiring 5,001
      additional units for just $13.50. The discount applies to ALL units in the order, not just those exceeding
      the threshold.</p>
    <p>This discount structure differs fundamentally from raw materials in two ways. First, the threshold is
      measured in units rather than kilograms, simplifying calculation. Second, only one discount tier exists
      rather than three, creating a binary decision: either reach 50,000+ units or don't pursue the discount. The
      10% savings translates to $2-3 per unit depending on the supplier, which compounds across quarterly orders
      and directly improves your Operating Margin.</p>
    <p>CONSOLIDATION OPPORTUNITY:</p>
    <p>Smart procurement teams recognize that this discount applies per component type per order. If you're
      ordering 30,000 Motherboards for SHA production and 25,000 Motherboards for ST production, consolidating
      into a single 55,000-unit order triggers the discount across all units. Similarly, teams might adjust
      production timing to concentrate orders in specific quarters, banking components as safety stock to capture
      the 10% savings. However, remember that holding costs and working capital implications must be weighed
      against discount benefits—see Chapter 11 for how inventory carrying costs affect your financial performance.
    </p>
    <h3 id="ch3-direct-to-dc-procurement">Direct-to-DC Procurement</h3>
    <p>Sub-assemblies (audio modules and control interfaces) can be procured directly from suppliers to your
      distribution centers, bypassing the manufacturing plant entirely. This option supports two scenarios: shell
      conversion at DCs, where you need product-specific sub-assemblies to complete postponed production, and
      replacement inventory for warranty repairs at regional locations.</p>
    <p>Direct-to-DC procurement uses the same supplier pricing and volume discounts as plant-bound orders.
      Transportation costs are uniform across all regions—shipping audio modules directly to DC3 costs the same as
      shipping to DC2. This flat-rate structure makes direct-to-DC procurement particularly attractive for distant
      regions like R3, where plant-to-DC shipping would otherwise incur higher transportation costs. However,
      direct-to-DC orders require an owned distribution center at the destination—outsourced DCs cannot receive
      direct supplier shipments for conversion purposes.</p>
    <p>Consider direct-to-DC procurement when you maintain significant shell inventory at regional DCs or when
      regional warranty volume justifies dedicated replacement stock. The uniform pricing across regions makes
      this especially valuable for R3 operations where plant-based shipping is most expensive.</p>
    <h3 id="ch3-motherboards-the-long-lead-time-component">Motherboards: The Long-Lead-Time Component</h3>
    <p>Motherboards are the most critical procurement decision because:</p>
    <ul>
      <li>QUARTER LEAD TIME: Order in Q1, receive in Q2</li>
      <li>SHARED COMPONENT: Both SHA and ST need them</li>
      <li>NO SUBSTITUTION: If you run out, you can't build anything</li>
    </ul>
    <p>SUPPLIER OPTIONS:</p>
    <table class="doc-table">
      <tr>
        <th>Supplier</th>
        <th>Cost</th>
        <th>Delivery Rate</th>
        <th>Failure Rate</th>
        <th>Notes</th>
      </tr>
      <tr>
        <td>D</td>
        <td>$29</td>
        <td>80%</td>
        <td>0.90%</td>
        <td>Premium, reliable, lowest failures</td>
      </tr>
      <tr>
        <td>E</td>
        <td>$20</td>
        <td>75%</td>
        <td>1.70%</td>
        <td>Budget option</td>
      </tr>
      <tr>
        <td>F</td>
        <td>$19</td>
        <td>77%</td>
        <td>2.20%</td>
        <td>Cheapest, highest failure rate</td>
      </tr>
      <tr>
        <td>G</td>
        <td>$21</td>
        <td>78%</td>
        <td>2.10%</td>
        <td>Mid-tier</td>
      </tr>
    </table>
    <p>DELIVERY RATE EXPLAINED:</p>
    <p>"80% ± 5%" means:</p>
    <ul>
      <li>Base delivery reliability: 80%</li>
      <li>Random variation: ±5 percentage points</li>
      <li>Actual delivery this quarter: Somewhere between 75% and 85%</li>
    </ul>
    <p>Example: You order 100k Motherboards from Supplier D</p>
    <ul>
      <li>Expected delivery: 80k units (80% rate)</li>
      <li>Possible range: 75k - 85k units</li>
      <li>Planning implication: You can't rely on receiving exactly 80k</li>
    </ul>
    <p>FAILURE RATE EXPLAINED:</p>
    <p>Of the units you receive, some are defective and unusable.</p>
    <p>Example: Supplier D delivers 80k units, failure rate 0.9%</p>
    <ul>
      <li>Usable units: 80k × (1 - 0.009) = 79,280</li>
      <li>Failed units: 720 (discarded or replaced)</li>
    </ul>
    <p>TOTAL YIELD:</p>
    <p>Yield = Delivery Rate × (1 - Failure Rate)</p>
    <ul>
      <li>Supplier D: 80% × 99.1% = 79.3% expected yield</li>
      <li>Supplier F: 77% × 97.8% = 75.3% expected yield</li>
    </ul>
    <p>So even though Supplier F costs $10 less per unit, you need to order 5% more to get the same usable
      quantity.</p>
    <h3 id="ch3-motherboard-procurement-strategy">Motherboard Procurement Strategy</h3>
    <p>CHALLENGE: You must order Q1 for Q2 production, but you don't know Q2 demand precisely.</p>
    <p>DECISION FRAMEWORK:</p>
    <p>STEP 1: Forecast Q2 production needs</p>
    <ul>
      <li>Estimate SHA demand: 60k units</li>
      <li>Estimate ST demand: 40k units</li>
      <li>Total Motherboards needed: 100k</li>
    </ul>
    <p>STEP 2: Add safety stock (recommended 10-15% for long-lead items)</p>
    <ul>
      <li>Safety stock: 10% = 10k units</li>
      <li>Total order: 110k Motherboards</li>
    </ul>
    <p>STEP 3: Account for supplier yield</p>
    <ul>
      <li>If using Supplier D (79.3% yield): 110k ÷ 0.793 = 138,700 order</li>
      <li>If using Supplier F (75.3% yield): 110k ÷ 0.753 = 146,100 order</li>
    </ul>
    <p>STEP 4: Calculate total cost</p>
    <ul>
      <li>Supplier D: 138,700 × $29 = $4,022,300</li>
      <li>Supplier F: 146,100 × $19 = $2,775,900</li>
      <li>APPARENT SAVINGS: $1,246,400 (31% cheaper)</li>
    </ul>
    <p>STEP 5: Account for failure consequences</p>
    <p>But Supplier F's higher failure rate (2.2% vs 0.9%) means:</p>
    <ul>
      <li>More defective finished products (SHA/ST failures)</li>
      <li>Higher warranty claims (if offered)</li>
      <li>Lower CSI (Product Quality score drops)</li>
      <li>Potential emergency procurement if yield is worse than expected</li>
    </ul>
    <h3 id="ch3-audio-modules-sha-only">Audio Modules (SHA Only)</h3>
    <p>Audio Modules are product-specific — only SHA units need them. They have immediate delivery (no lead time).
    </p>
    <p>SUPPLIER OPTIONS:</p>
    <table class="doc-table">
      <tr>
        <th>Supplier</th>
        <th>Cost</th>
        <th>Delivery Rate</th>
        <th>Failure Rate</th>
        <th>Notes</th>
      </tr>
      <tr>
        <td>A</td>
        <td>$12</td>
        <td>80%</td>
        <td>2.00%</td>
        <td>Mid-range option</td>
      </tr>
      <tr>
        <td>B</td>
        <td>$14</td>
        <td>85%</td>
        <td>1.90%</td>
        <td>Standard option, slightly better failure rate</td>
      </tr>
      <tr>
        <td>C</td>
        <td>$13</td>
        <td>85%</td>
        <td>2.00%</td>
        <td>Similar to A but better delivery</td>
      </tr>
      <tr>
        <td>D</td>
        <td>$22</td>
        <td>90%</td>
        <td>1.20%</td>
        <td>Premium, highest reliability, lowest failures</td>
      </tr>
    </table>
    <p>KEY STRATEGIC CONSIDERATION: Audio Module failures flow through to SHA Product Quality score. Choose
      suppliers based on your differentiation strategy.</p>
    <h3 id="ch3-control-interfaces-st-only">Control Interfaces (ST Only)</h3>
    <p>Control Interfaces are product-specific — only ST units need them. They have immediate delivery and
      represent a critical quality component for the ST product line.</p>
    <p>SUPPLIER OPTIONS:</p>
    <table class="doc-table">
      <tr>
        <th>Supplier</th>
        <th>Cost</th>
        <th>Delivery Rate</th>
        <th>Failure Rate</th>
        <th>Notes</th>
      </tr>
      <tr>
        <td>B</td>
        <td>$15</td>
        <td>75%</td>
        <td>2.60%</td>
        <td>Mid-range option; New mid-tier option</td>
      </tr>
      <tr>
        <td>C</td>
        <td>$16</td>
        <td>78%</td>
        <td>2.50%</td>
        <td>Balanced quality-cost; Balanced quality-cost</td>
      </tr>
      <tr>
        <td>D</td>
        <td>$24</td>
        <td>80%</td>
        <td>1.80%</td>
        <td>Premium, highest reliability, lowest failures; Premium, highest quality</td>
      </tr>
      <tr>
        <td>E</td>
        <td>$14</td>
        <td>70%</td>
        <td>2.70%</td>
        <td>Budget option; Budget option</td>
      </tr>
      <tr>
        <td>F </td>
        <td> $ 13 </td>
        <td> 70 % </td>
        <td> 2.80 % </td>
        <td> Lowest cost, highest failure rate; Lowest cost, longest lead </td>
      </tr>
    </table>
    <p> UNDERSTANDING THE METRICS:</p>
    <p><strong>Quality %:</strong> Base quality rating of the component. Higher quality reduces defect rates in
      finished ST units and improves Product Quality scores in CSI calculations.</p>
    <p><strong>Lead Time %:</strong> Percentage of standard delivery time required. Lower percentages mean faster
      delivery, though all Control Interfaces have immediate availability compared to Motherboards' one-quarter
      lead time.</p>
    <p><strong>Cost per Quality Point:</strong> Efficiency metric calculated as (Cost ÷ Quality %). Lower values
      indicate better value — you're paying less per unit of quality received. Supplier D offers the best value at
      1.80% despite its premium price, while Supplier F has the worst value proposition at 2.80%.</p>
    <p>KEY STRATEGIC CONSIDERATIONS:</p>
    <p>Control Interface quality directly impacts ST Product Quality scores. If pursuing a differentiation
      strategy for ST units, premium suppliers (B, C, or D) provide better quality-to-cost ratios despite higher
      upfront costs. The Cost per Quality Point metric helps identify which suppliers deliver the best value —
      Supplier D leads at 1.80%, followed by Supplier C at 2.50%. Budget-conscious teams may favor Suppliers E or
      F, but recognize the trade-off: while you save $10-11 per unit versus Supplier D, the 10-percentage-point
      quality gap will reduce your ST Product Quality scores and potentially harm CSI ratings in competitive
      markets.</p>
    <h2 id="ch3-part-3-supplier-relationships-and-it-synchronization">Part 3: Supplier Relationships And IT
      Synchronization</h2>
    <h3 id="ch3-supplier-relationship-costs">Supplier Relationship Costs</h3>
    <p>Each supplier relationship carries fixed costs:</p>
    <ul>
      <li>Quarterly maintenance fee: $5,000 per supplier</li>
      <li>Per-order transaction cost: $1,250 per order</li>
    </ul>
    <p>Example: If you use Suppliers A, D, and F:</p>
    <ul>
      <li>Quarterly fees: 3 × $5,000 = $15,000</li>
      <li>If you place 3 orders total: 3 × $1,250 = $3,750</li>
      <li>Total relationship cost: $18,750</li>
    </ul>
    <p>CONSOLIDATION STRATEGY: Notice that Supplier D provides both Motherboards and Control Interfaces. If you're
      already using D for Motherboards, adding Control Interfaces from D only adds the $1,250 order cost, not
      another $5,000 relationship fee.</p>
    <h3 id="ch3-it-synchronization-investment">IT Synchronization Investment</h3>
    <p>You can invest in IT system synchronization with suppliers to improve delivery reliability and reduce
      failure rates. This is an optional investment per supplier with both one-time setup and ongoing quarterly
      costs.</p>
    <p>COST STRUCTURE:</p>
    <p>Initial setup (one-time): $6,000-9,000 depending on supplier</p>
    <p>Quarterly maintenance: $5,000-9,000 depending on supplier</p>
    <p>See Chapter 10 for detailed supplier-specific costs and benefits.</p>
    <p>BENEFIT:</p>
    <p>Improves supplier delivery reliability (+3% to +6%) and reduces component failure rates (-0.1% to -0.5%)
      through automated information sharing and better quality control.</p>
    <p>ROI CALCULATION:</p>
    <p>If ordering 100k Motherboards at 2.1% failure rate:</p>
    <ul>
      <li>Expected failures: 2,100 units</li>
    </ul>
    <p>With IT sync (typical 0.4% reduction): 1,700 failures</p>
    <p>Savings: 400 fewer defective units</p>
    <p>Value: 400 × $150 impact = $60,000 benefit</p>
    <ul>
      <li>Quarterly IT cost: ~$6,000</li>
    </ul>
    <p>Net benefit: $54,000 per quarter</p>

    <h2 id="ch3-part-4-emergency-procurement">Part 4: Emergency Procurement</h2>
    <p>If you underorder and run short, FLEXEE automatically triggers emergency procurement with severe penalties:
    </p>
    <p>RAW MATERIALS EMERGENCY:</p>
    <p>Plastics: +$1/kg surcharge (base $4 → emergency $5)</p>
    <p>Casings: +$1/kg surcharge (base $5 → emergency $6)</p>
    <p>Administrative fee: $2,000 per supplier per quarter</p>
    <p>SUB-ASSEMBLY EMERGENCY:</p>
    <p>Motherboards: +$8/unit surcharge</p>
    <p>Audio Modules: +$6/unit surcharge</p>
    <p>Control Interfaces: +$4/unit surcharge</p>
    <p>Administrative fee: $2,000 per component per quarter</p>
    <p>WHY SO EXPENSIVE?</p>
    <p>In real supply chains, emergency orders mean:</p>
    <p>Expedited shipping (air freight instead of ocean)</p>
    <p>Rush handling at supplier facilities (overtime labor)</p>
    <p>Opportunity cost (supplier prioritizes you over other customers)</p>
    <p>Administrative burden (rush paperwork, special invoicing)</p>
    <p>IMPACT ON FINANCIALS:</p>
    <p>Emergency procurement significantly increases your COGS, destroying gross margins. See Chapter 11 for how
      emergency costs appear on the Income Statement.</p>
    <h2 id="ch3-chapter-summary-key-takeaways">Chapter Summary: Key Takeaways</h2>
    <ul>
      <li>Volume Discounts Matter Enormously: Hitting tier 3 discounts (1M kg) saves $600k+/quarter on raw
        materials. Consolidate orders.</li>
    </ul>
    <ul>
      <li>Sub-Assembly Volume Discounts Reward Scale: Orders exceeding 50,000 units earn 10% discount on
        Motherboards, Audio Modules, or Control Interfaces. Consolidate SHA and ST orders to reach threshold.</li>
    </ul>
    <ul>
      <li>Motherboards Require Two-Quarter Planning: Q1 order → Q2 arrival. Forgetting this is the #1 procurement
        mistake. See Chapter 3 for detailed motherboard procurement mechanics and why this lead time requires
        two-quarter planning horizons.</li>
      <li>Supplier Choice Is Total Cost Of Ownership: Cheapest supplier ≠ lowest total cost. Account for failures,
        delivery reliability, and emergency risk. Defect rates directly impact your Product Quality scores. See
        Chapter 12 for how supplier quality affects your overall performance evaluation.</li>
      <li>Safety Stock Is Insurance: 5-10% buffer costs 3-5% in holding charges but avoids 75-100% emergency
        premiums. Safety stock strategy should align with your forecast accuracy—see Chapter 9 for forecasting
        methods that reduce the need for large safety stock buffers.</li>
      <li>Emergency Procurement Destroys Margins: Emergency costs $15-20 extra per unit. Avoid at all costs
        through planning.</li>
      <li>Consolidate Suppliers When Possible: Each supplier costs $5k/quarter + $1,250/order. Use Supplier D for
        multiple components.</li>
      <li>IT Sync Pays Off At High Volumes: If ordering 50k+ units/quarter, IT sync (cost $5-9k/quarter) saves
        $30-60k/quarter.</li>
    </ul>
  </section>
@endsection
