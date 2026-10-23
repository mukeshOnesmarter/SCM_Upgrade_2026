@extends('student.manual.layout')
@section('title', 'Chapter 4: Manufacturing Decisions')
@section('template')
  <section id="chapter-4-manufacturing-production-decisions">
    <h1>CHAPTER 4: MANUFACTURING & PRODUCTION DECISIONS</h1>
    <div class="learning-objectives">
      <table class="doc-table">
        <tr>
          <td>
            <h2>Learning Objectives:</h2>
            <p>By the end of this chapter, you will be able to:</p>
            <ul>
              <li>Plan production quantities for SHA and ST based on demand forecasts and component availability
              </li>
              <li>Differentiate between regular and emergency production mechanics and their cost structures</li>
              <li>Apply postponement strategies using shells to maintain production flexibility</li>
              <li>Calculate total production costs, including materials, labor, overhead, and premium charges</li>
              <li>Evaluate how production decisions impact inventory levels, cash flow, working capital, and
                capacity-driven product mix choices</li>
            </ul>
          </td>
        </tr>
      </table>
    </div>
    <h2 id="ch4-production-as-the-value-creation-engine">Production As The Value Creation Engine</h2>
    <p>Production is where you transform raw materials and components into products customers will buy. It is the
      bridge between procurement (inputs) and distribution (outputs).</p>
    <p>Production decisions determine:</p>
    <ul>
      <li>What you can sell (can not sell what you don't build)</li>
      <li>Your inventory levels (overproduction ties up cash)</li>
      <li>Your cost structure (regular vs. emergency production)</li>
      <li>Your customer satisfaction (underproduction → stockouts → CSI damage). See Chapter 7 for how service
        levels and Chapter 12 for how Availability Quality (AQ) is measured within CSI.</li>
      <li>Your flexibility (Shells enable postponement)</li>
    </ul>
    <p>Real-World Context: Production Strategy as Competitive Weapon</p>
    <table class="doc-table">
      <tr>
        <th>TOYOTA PRODUCTION SYSTEM (TPS)</th>
        <th>DELL'S BUILD-TO-ORDER (2000s):</th>
      </tr>
      <tr>
        <td>"Just-in-time" production: Build what's needed, when it's needed
          Minimal inventory (reduces working capital)
          Result: 50% less inventory than competitors, higher ROA</td>
        <td>Zero finished goods inventory
          Assemble only after customer orders
          Result: 8-day inventory turns (vs 30+ for Compaq, HP)</td>
      </tr>
      <tr>
        <td>FLEXEE SHELLS = Dell + Zara hybrid: Build partially completed units, finish when demand is clearer.
          Trade-off: Higher conversion costs vs. better Fill Rate.</td>
        <td>FLEXEE SHELLS = Dell + Zara hybrid: Build partially completed units, finish when demand is clearer.
          Trade-off: Higher conversion costs vs. better Fill Rate.</td>
      </tr>
    </table>
    <h2 id="ch4-regular-production">Regular Production</h2>
    <p>Production Inputs</p>
    <p>Manufacturing one SHA unit requires a precise bill of materials. The raw materials component includes five
      kilograms each of plastics and casings. For sub-assemblies, one motherboard and one audio module are
      required.</p>
    <p>Building an ST unit follows a similar pattern with one key difference. While the raw materials (five
      kilograms each of plastics and casings) and motherboard (one unit) remain identical to the SHA, the ST
      requires a control interface rather than an audio module.</p>
    <p>Production Constraint:
      You can only build what you have components for. If you have 80k Motherboards, you can build maximum 80k
      total units (any combination of SHA + ST ≤ 80k).</p>
    <h2 id="ch4-emergency-production">Emergency Production</h2>
    <p>Emergency production is FLEXEE's safety valve—automatic additional production when regular production falls
      short of demand. Emergency production also negatively impacts your Production Deviation KPI—see Chapter 12
      for how this affects your Operational Excellence score.</p>
    <h3 id="ch4-how-emergency-production-works">How Emergency Production Works:</h3>
    <p>Emergency production activates automatically when regular production falls short of customer demand. The
      system builds additional units up to your declared emergency limit, but these units incur substantial cost
      premiums based on volume thresholds. The first 5,000 emergency units carry a 50% production cost premium
      above regular manufacturing costs. Any emergency production beyond 5,000 units doubles this penalty to a
      100% premium. Emergency units also ship by air to customers rather than surface transport, adding
      approximately 50% to transportation costs. Finally, you pay standby charges based on your declared emergency
      capacity limit regardless of whether you actually trigger emergency production.</p>
    <h3 id="ch4-emergency-production-cost-structure">Emergency Production Cost Structure</h3>
    <p>The standby cost for emergency production runs $0.50 per unit of your declared capacity limit per quarter.
      If you declare a 10,000-unit SHA emergency limit, you pay $5,000 per quarter whether or not you actually use
      any emergency capacity. This standby charge reflects the cost of maintaining production flexibility and
      capacity reserves.</p>
    <p>When emergency production actually triggers, usage costs compound quickly through multiple penalty layers.
      The base emergency charge is $2 per SHA unit or $3 per ST unit. On top of this base, you pay production
      premiums: 50% of regular production cost for the first 5,000 units, then 100% of regular production cost for
      units beyond 5,000. Emergency units also require expedited air shipping to customers, adding roughly $20 per
      unit in transportation premiums.</p>
    <p>Consider the total impact for 3,000 SHA emergency units. Regular SHA production costs $92 per unit for
      materials alone. Emergency production adds a $6,000 base charge (3,000 units × $2), a $138,000 production
      premium (3,000 units × $92 × 50%), and approximately $60,000 in emergency air shipping (3,000 units × $20).
      The total emergency cost reaches $204,000, or $68 extra per unit beyond the regular $92 cost. Each emergency
      SHA therefore costs $160—74% more expensive than regular production. This dramatic cost penalty makes
      emergency production viable only as a safety net for occasional demand spikes, never as a deliberate
      strategy.</p>
    <p>Unfilled Orders</p>
    <p>When total demand exceeds your combined regular and emergency production capacity, the shortfall results in
      unfilled orders. These orders cannot be satisfied in the current quarter and are not carried forward as
      backorders to future quarters. Unfilled orders represent lost sales that permanently damage your customer
      relationships and market position.</p>
    <p>Each unfilled order incurs a $25 per unit processing and handling cost. This administrative cost covers
      order cancellation processing, customer service interactions, and reputation management efforts. Beyond
      these direct costs, unfilled orders reduce your Fill Rate, which directly damages your Customer Satisfaction
      Index (CSI). Lower CSI reduces future quarter demand as disappointed customers shift to competitors or
      reduce their purchase intentions.</p>
    <p>The cascading impact of unfilled orders makes them far more expensive than the $25 processing cost
      suggests. An unfilled order in Q2 costs $25 immediately, reduces Q2 Fill Rate (lowering Q2 CSI), and
      suppresses Q3 demand through the CSI carryover effect. The total economic impact of one unfilled unit can
      reach $50-75 when accounting for lost margin, CSI damage, and reduced future demand.</p>
    <p>Managing Unfilled Order Risk</p>
    <p>Avoid unfilled orders through proactive planning rather than reactive fixes. Three strategies prevent
      unfilled orders: First, maintain safety stock equal to 5-10% of quarterly demand to absorb forecast errors
      without triggering emergency production. Second, invest in forecast accuracy improvement (see Chapter 9) to
      reduce the likelihood of demand surprises. Third, set emergency production limits at 10-15% of regular
      production to provide adequate buffer capacity.</p>
    <p>Production volume flexibility (see previous section) provides an additional safeguard. If demand volatility
      exceeds ±10,000 units quarterly, investing in flexibility ($10,000 setup + tiered variable costs) allows
      production adjustments that prevent both emergency production and unfilled orders. Compare flexibility costs
      ($2-10/unit) against the combined costs of emergency production (75-100% premiums) and unfilled orders
      ($25/unit + CSI damage).</p>
    <p>Emergency production serves as your primary defense against unfilled orders, but remember its dual penalty:
      production cost premiums (50-100%) and Production Deviation KPI damage. Unfilled orders represent the
      catastrophic failure case—production planning has failed completely, emergency production capacity proves
      insufficient, and you're now paying $25/unit while losing customers. Design your production strategy to make
      unfilled orders extremely rare, ideally occurring less than once per year and affecting fewer than 1-2% of
      demand when they do occur.</p>
    <h3 id="ch4-production-volume-flexibility">Production Volume Flexibility</h3>
    <p>Production volume flexibility allows you to adjust production levels quarter-to-quarter beyond the natural
      constraints of regular production planning. Without flexibility investment, production changes between
      quarters are limited by component lead times, shift capacity, and operational inertia. Investing in
      production volume flexibility expands your ability to respond to demand changes, forecast corrections, and
      market opportunities by increasing or decreasing production levels more aggressively than standard
      operations allow.</p>
    <p>The maximum production change from one quarter to the next is 10,000 units per product without flexibility
      investments. If Q1 SHA production was 30,000 units, Q2 production without flexibility can range from 20,000
      to 40,000 units (±10,000). Production volume flexibility extends this range by purchasing additional
      capacity to respond to larger demand swings.</p>
    <p>Flexibility Investment Structure</p>
    <p>Adding production volume flexibility requires a one-time setup investment of $10,000 per product. This
      initial cost covers process redesign, supplier coordination, and workforce training to enable more
      responsive production adjustments. The setup investment is product-specific: you can add flexibility to SHA
      only, ST only, or both products depending on your demand volatility patterns.</p>
    <p>Once the initial setup is complete, incremental flexibility follows a tiered cost structure based on how
      much additional responsiveness you need:</p>
    <p>Flexibility Tiers (Beyond Base 10,000 Unit Change)</p>
    <p>Tier 1 (1-2,500 additional units):</p>
    <p>• Fixed cost: $0</p>
    <p>• Variable cost: $2 per unit of flexibility purchased</p>
    <p>• Total capacity change: Up to 12,500 units from previous quarter</p>
    <p>Tier 2 (2,501-5,000 additional units):</p>
    <p>• Fixed cost: $5,000 (one-time)</p>
    <p>• Variable cost: $4 per unit over 2,500</p>
    <p>• Total capacity change: Up to 15,000 units from previous quarter</p>
    <p>Tier 3 (5,001-7,500 additional units):</p>
    <p>• Fixed cost: $15,000 (one-time)</p>
    <p>• Variable cost: $6 per unit over 5,000</p>
    <p>• Total capacity change: Up to 17,500 units from previous quarter</p>
    <p>Tier 4 (7,501-10,000 additional units):</p>
    <p>• Fixed cost: $30,000 (one-time)</p>
    <p>• Variable cost: $8 per unit over 7,500</p>
    <p>• Total capacity change: Up to 20,000 units from previous quarter</p>
    <p>Tier 5 (10,001-12,500 additional units):</p>
    <p>• Fixed cost: $50,000 (one-time)</p>
    <p>• Variable cost: $10 per unit over 10,000</p>
    <p>• Total capacity change: Up to 22,500 units from previous quarter</p>
    <p>The tiered structure means you pay both the fixed cost for reaching that tier plus the cumulative variable
      costs for all units of flexibility purchased. Higher tiers become progressively more expensive, reflecting
      the operational complexity of enabling larger production swings.</p>
    <p>Cost Calculation Examples</p>
    <p>Example 1: Adding 2,000 units of flexibility to SHA</p>
    <p>• Setup cost (first time only): $10,000</p>
    <p>• Tier 1 variable cost: 2,000 units × $2 = $4,000</p>
    <p>• Total first-time investment: $14,000</p>
    <p>• New maximum quarterly change: 12,000 units (10,000 base + 2,000 flexibility)</p>
    <p>Example 2: Adding 4,000 units of flexibility to ST</p>
    <p>• Setup cost (first time only): $10,000</p>
    <p>• Tier 1 cost: 2,500 × $2 = $5,000</p>
    <p>• Tier 2 fixed: $5,000</p>
    <p>• Tier 2 variable: 1,500 units × $4 = $6,000</p>
    <p>• Total first-time investment: $26,000</p>
    <p>• New maximum quarterly change: 14,000 units (10,000 base + 4,000 flexibility)</p>
    <p>Example 3: Adding 8,000 units of flexibility to SHA</p>
    <p>• Setup cost (first time only): $10,000</p>
    <p>• Tier 1: 2,500 × $2 = $5,000</p>
    <p>• Tier 2: $5,000 fixed + (2,500 × $4) = $15,000</p>
    <p>• Tier 3: $15,000 fixed + (2,500 × $6) = $30,000</p>
    <p>• Tier 4: $30,000 fixed + (500 × $8) = $34,000</p>
    <p>• Total first-time investment: $99,000</p>
    <p>• New maximum quarterly change: 18,000 units (10,000 base + 8,000 flexibility)</p>
    <p>Strategic Considerations</p>
    <p>Production volume flexibility serves as insurance against forecast errors and demand volatility.
      High-growth markets or products with uncertain demand patterns benefit most from flexibility investments. If
      your SHA forecast accuracy is ±30% (meaning a 40,000 unit forecast could be 28,000-52,000 actual), the base
      10,000 unit adjustment may prove insufficient, triggering emergency production with its 75-100% cost
      premiums. Investing $14,000 for 2,000 units of flexibility (Tier 1) provides safety margin against forecast
      errors.</p>
    <p>The tiered cost structure creates natural decision points. Tier 1 flexibility (1-2,500 units) at $2 per
      unit represents affordable insurance for most products. Tier 2 crosses a threshold with the $5,000 fixed
      cost, making sense only when forecast uncertainty or market volatility justify the investment. Higher tiers
      become progressively expensive and are typically reserved for products with extreme demand volatility or
      during major product launches.</p>
    <p>Flexibility is product-specific, allowing differentiated strategies. If SHA shows stable demand but ST
      faces seasonal swings, invest flexibility in ST only rather than both products. The $10,000 setup cost makes
      partial implementation viable, and you can add flexibility incrementally as demand patterns clarify.</p>
    <p>Compare flexibility costs against emergency production penalties. Emergency production costs 75-100%
      premium over regular production, emergency procurement adds 50-75% to component costs, and emergency air
      shipping raises transportation costs by 50%. If forecast errors frequently trigger emergency mechanisms
      costing $80,000-150,000 per quarter, investing $26,000 in Tier 2 flexibility (4,000 units) delivers ROI
      within one or two quarters.</p>
    <p>Timing matters. Add flexibility before volatile quarters, not after. Installing SHA flexibility in Q1
      allows you to respond to Q2 demand surprises. Waiting until Q2 to add flexibility means Q2 remains
      constrained by the original 10,000 unit limit. Anticipate volatility based on product lifecycle: new product
      launches warrant immediate flexibility investment, while mature products with stable demand rarely justify
      the cost.</p>
    <p>Production volume flexibility integrates with other operational levers. Shells provide demand flexibility
      between SHA and ST but don't expand total production capacity. Distribution centers enable geographic
      flexibility but don't change manufacturing volume constraints. Emergency production serves as a safety net
      but carries punitive cost premiums. Flexibility investments specifically target the production volume
      adjustment problem, complementing rather than replacing other supply chain strategies.</p>
    <p>When to Invest in Production Volume Flexibility</p>
    <p>Invest in flexibility when:</p>
    <p>• Forecast accuracy is below 80% (forecast errors exceed 20%)</p>
    <p>• Product is in growth phase with uncertain trajectory</p>
    <p>• Seasonal demand patterns create regular volatility</p>
    <p>• New product launch makes Q1-Q2 demand especially uncertain</p>
    <p>• Emergency production has triggered in recent quarters</p>
    <p>• Marketing campaigns or price changes create demand uncertainty</p>
    <p>Skip flexibility when:</p>
    <p>• Demand is stable with &lt;10% quarterly variation</p>
    <p>• Mature product with high forecast accuracy (&gt;90%)</p>
    <p>• Emergency production costs have been minimal</p>
    <p>• Budget constraints prevent the $10,000+ investment</p>
    <p>• Product nearing end-of-life with declining volumes</p>
    <h2 id="ch4-shells-postponement-strategy">Shells (Postponement Strategy)</h2>
    <p>Shells are FLEXEE's most sophisticated production concept—a form of postponement where you build partially
      completed units and delay the final assembly decision.</p>
    <p>What Are Shells?</p>
    <p>Shells are products built with nine kilograms each of plastics and casings, plus one motherboard—the
      component common to both SHA and ST products. Shells contain no product-specific sub-assembly (no audio
      module or control interface). Think of shells as "generic" platform units that can become either SHA or ST
      later when you add only the product-specific sub-assembly during conversion.</p>
    <p>Where Shells Are Held</p>
    <p>Shells can only be stored at owned distribution centers, not at outsourced third-party logistics
      facilities. This storage restriction means postponement strategies require upfront capital investment in
      distribution infrastructure. The shell production and conversion process follows three stages: you build
      shells at your Region 1 plant, ship them to owned distribution centers in R2 or R3, and then convert shells
      into finished SHA or ST units at the distribution center when actual demand patterns become clear.</p>
    <h3 id="ch4-how-shell-conversion-works">How Shell Conversion Works</h3>
    <p>Shell production begins at your manufacturing plant where you build partially completed units. Each shell
      costs approximately $87.61, comprising $29.16 for plastics (9kg × $3.24 assuming tier 3 volume discounts),
      $36.45 for casings (9kg × $4.05), and $22.00 for one motherboard. The shell contains the motherboard but no
      product-specific sub-assemblies—only the audio module or control interface is added during the conversion
      process at distribution centers. You ship these shells to owned distribution centers at 50% of the
      transportation cost for finished goods. Despite shells being heavier due to extra raw materials (9kg
      plastics + 9kg casings vs 5kg + 5kg for finished units) and including the motherboard, the simpler packaging
      reduces handling complexity, resulting in the 50% transportation cost advantage.</p>
    <p>Conversion happens at the distribution center when demand becomes clearer. To convert a shell into an SHA
      unit, you add one audio module (approximately $15) and packaging ranging from $10 to $28 depending on your
      quality choice, for total added costs of $25 to $43. Converting a shell to an ST unit requires one control
      interface (approximately $16) and the same packaging options, totaling $26 to $44 in additional costs. Since
      the motherboard is already included in the shell, conversion only requires adding the product-specific
      sub-assembly (AM for SHA, CI for ST). This conversion flexibility allows you to respond to actual market
      demand rather than committing to specific product configurations weeks in advance.</p>
    <h3 id="ch4-production-cost-comparison-across-locations">Production Cost Comparison Across Locations</h3>
    <p>The decision between producing finished goods at the plant versus using shells for DC conversion requires
      understanding the full cost structure at each production location.</p>
    <p>Production at the manufacturing plant operates on a shift-based system with fixed capacity limits and
      costs. Each production shift at the plant supports a maximum production capacity of 50,000 units across all
      products combined (SHA, ST, and shells). This capacity limit applies to your total quarterly plant
      production: if you produce 30,000 SHA units, 15,000 ST units, and 10,000 shells at the plant in a quarter,
      you have used 55,000 units of capacity, requiring two production shifts.</p>
    <p>Each plant production shift incurs a fixed cost of $100,000 per quarter, independent of which products you
      manufacture. Producing 10,000 units at the plant requires one shift ($100,000). Producing 75,000 units at
      the plant requires two shifts ($200,000), as the first shift accommodates 50,000 units and the second shift
      covers the remaining 25,000 units. These plant shift costs are in addition to the per-order fixed costs and
      per-unit labor and production costs detailed below. Distribution center production does not use shifts. DC
      production costs consist only of the per-order fixed costs and per-unit labor and production costs shown in
      the table below, with no additional shift-based charges.</p>
    <p>Strategic production planning must account for plant shift costs when deciding between plant and DC
      production. Producing 49,000 units at the plant costs one shift ($100,000), but producing 51,000 units
      requires two shifts ($200,000)—crossing the 50,000-unit threshold doubles the shift cost for only 4% more
      production. Consider whether additional plant volume justifies crossing capacity thresholds, or whether
      splitting production between plant and DCs offers better cost efficiency.</p>
    <p>Manufacturing Costs (Per Unit)</p>
    <table class="doc-table">
      <tr>
        <th></th>
        <th>Manufacturing Plant</th>
        <th>Postponed Production</th>
        <th>Postponed Production</th>
        <th>Postponed Production</th>
      </tr>
      <tr>
        <td></td>
        <td></td>
        <td>DC1</td>
        <td>DC2</td>
        <td>DC3</td>
      </tr>
      <tr>
        <td>Postponed Production</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td>Fixed Costs (per order)</td>
        <td>$20,000</td>
        <td></td>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td>Labor Costs (per unit)</td>
        <td>$22</td>
        <td></td>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td>Production Costs (per unit)</td>
        <td>$11</td>
        <td></td>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td>Smart Home Assistant (SHA)</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td>Fixed Costs (per order)</td>
        <td>$22,500</td>
        <td>$5,000</td>
        <td>$5,000</td>
        <td>$4,000</td>
      </tr>
      <tr>
        <td>Labor Costs (per unit)</td>
        <td>$30</td>
        <td>$14</td>
        <td>$15</td>
        <td>$12</td>
      </tr>
      <tr>
        <td>Production Costs (per unit)</td>
        <td>$20</td>
        <td>$12</td>
        <td>$14</td>
        <td>$11</td>
      </tr>
      <tr>
        <td>Smart Thermostat (ST)</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
      </tr>
      <tr>
        <td>Fixed Costs (per order)</td>
        <td>$24,500</td>
        <td>$6,000</td>
        <td>$8,000</td>
        <td>$5,000</td>
      </tr>
      <tr>
        <td>Labor Costs (per unit)</td>
        <td>$36</td>
        <td>$16</td>
        <td>$20</td>
        <td>$15</td>
      </tr>
      <tr>
        <td>Production Costs (per unit)</td>
        <td>$16</td>
        <td>$10</td>
        <td>$12</td>
        <td>$10</td>
      </tr>
    </table>
    <p>The table reveals why the shell strategy involves trade-offs:</p>
    <ul>
      <li>Plant production captures economies of scale with lower per-unit labor costs ($22-$36) but higher fixed
        costs per order ($20,000-$24,500)</li>
      <li>DC production has lower fixed costs per order ($4,000-$8,000) but higher per-unit labor costs ($12-$20)
        due to smaller-scale operations</li>
      <li>Postponed production (shells) is only available at the plant, requiring upfront investment but enabling
        flexible conversion at DCs</li>
    </ul>
    <p>For example, producing 10,000 SHA units entirely at the plant costs $22,500 (fixed) + $500,000 (labor +
      production) = $522,500 total. Converting 10,000 shells at DC3 costs $4,000 (fixed) + $230,000 (labor +
      production at DC) + $876,100 (shell costs at $87.61 each) + $150,000 (audio modules at $15 each) +
      transportation. Since shells already contain motherboards, conversion only requires adding the
      product-specific sub-assembly. While shells are heavier than finished goods, the strategy provides demand
      flexibility that can reduce inventory costs and improve fill rates when forecast uncertainty is high.</p>
    <h3 id="ch4-why-use-shells">Why Use Shells?</h3>
    <p>The demand flexibility benefit becomes clear when facing forecast uncertainty. Imagine you are planning Q2
      production with SHA forecasted at 40,000 units and ST at 30,000 units, but both forecasts carry ±20%
      uncertainty. Building directly to forecast—40,000 SHA and 30,000 ST—creates substantial risk. If actual
      demand shifts to 50,000 SHA and only 20,000 ST, you face SHA stockouts while holding 10,000 excess ST units.
    </p>
    <p>A shell-based strategy manages this uncertainty more effectively. Build 30,000 SHA units to cover certain
      minimum demand, 20,000 ST units for the same reason, and 20,000 shells as a flexibility buffer. When Q2
      actual demand materializes, convert the shells appropriately: if SHA demand proves stronger, convert shells
      to SHA; if ST demand exceeds expectations, convert shells to ST. This postponement approach trades higher
      conversion and holding costs for dramatically improved fill rates and reduced stockout risk. The strategy
      proves most valuable when demand uncertainty exceeds ±15%, as the cost of improved customer satisfaction and
      avoided lost sales typically outweighs the incremental shell conversion expenses.</p>
    <h2 id="ch4-chapter-summary-key-takeaways">Chapter Summary: Key Takeaways</h2>
    <ul>
      <li>Production Matches Component Availability
        Motherboard constraint is typically binding.</li>
      <li>Emergency Production Costs 75-100% Premium
        Use as safety net, not strategy.</li>
      <li>Shells Provide Flexibility But Require Owned DC’s
        Use when demand uncertainty is ±15%+.</li>
      <li>Emergency Limits Have Standby Costs ($0.50/Unit/Quarter)
        Set limits based on forecast uncertainty. 5k is typical.</li>
      <li>Shells Improve Fill Rate And Availability Quality
        Trade-off: Higher costs (holding, conversion) for better CSI.</li>
      <li>Overproduction Ties Up Cash (Working Capital Trap)
        Building 20% above demand means cash locked in FGI for quarters.</li>
      <li>Fill Rate Drives CSI, Which Drives Future Demand
        Underproduction today damages CSI and reduces next quarter's demand.</li>
    </ul>
  </section>
@endsection
