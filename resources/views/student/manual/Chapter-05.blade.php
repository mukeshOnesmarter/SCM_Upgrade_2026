@extends('student.manual.layout')
@section('title', 'Chapter 5: Distribution Decisions')
@section('template')
  <section id="chapter-5-distribution-decisions">
    <h1>CHAPTER 5: DISTRIBUTION DECISIONS</h1>
    <div class="learning-objectives">
      <table class="doc-table">
        <tr>
          <td>
            <h2>Learning Objectives:</h2>
            <p>By the end of this chapter, you will be able to:</p>
            <ul>
              <li>Evaluate distribution center (DC) options, including no DC, outsourced DC, and owned DC</li>
              <li>Calculate DC breakeven points based on regional volume and transportation savings</li>
              <li>Understand how DC decisions affect inventory costs, cash flow, and return on assets (ROA)</li>
              <li>Decide RFID strategies, choosing between outsourcing and insourcing based on volume thresholds
              </li>
              <li>Analyze cross-docking benefits and trace how distribution decisions flow through financial
                statements</li>
            </ul>
          </td>
        </tr>
      </table>
    </div>
    <h2 id="ch5-introduction-distribution-as-strategic-positioning">Introduction: Distribution As Strategic
      Positioning</h2>
    <p>Distribution determines HOW you deliver products to customers and WHERE you hold inventory. These decisions
      affect:</p>
    <ul>
      <li>Transportation costs (ship from plant vs. ship from regional DC)</li>
      <li>Inventory costs (holding charges increase with DCs)</li>
      <li>Customer service (closer inventory = faster delivery)</li>
      <li>Capital requirements (owned DCs require $300k investment)</li>
      <li>Return on Assets (more DCs = more assets = lower ROA unless volume justifies it)</li>
    </ul>
    <h2 id="ch5-part-1-distribution-center-options">Part 1: Distribution Center Options</h2>
    <p>For each region (R1, R2, R3), you must choose a distribution strategy:</p>
    <h3 id="ch5-option-0-no-distribution-center">Option 0: No Distribution Center</h3>
    <p>The simplest distribution strategy involves no regional distribution center at all. Under this approach,
      you serve each region directly from your Region 1 plant using air freight. This eliminates both the opening
      costs and ongoing operational expenses associated with distribution centers. However, per-unit
      transportation costs are high due to the expense of air shipping. Since no regional inventory exists, you
      incur no inventory holding charges, but you also forfeit the ability to use shells, which require owned
      distribution centers for conversion operations.</p>
    <h3 id="ch5-option-1-outsourced-distribution-center">Option 1: Outsourced Distribution Center</h3>
    <p>An outsourced distribution center relies on a third-party logistics provider to operate the facility on
      your behalf. Opening an outsourced DC requires a $125,000 initial investment, with a $60,000 closing cost if
      you later decide to exit. When closing an outsourced DC, any inventory remaining at the facility can be
      disposed of at 20% of its value—meaning you recover $0.20 for every dollar of inventory value at closing.
      Quarterly operating costs run $60,000 as a fixed expense regardless of volume. Inventory held at outsourced
      facilities carries a 5% holding charge per quarter, calculated on the value of inventory stored there. The
      facility can hold finished goods inventory, providing regional presence and reduced transportation costs.
      However, outsourced providers lack the assembly capability required to convert shells, limiting your
      strategic flexibility. This option works well for volume-driven strategies where postponement is
      unnecessary.</p>
    <h3 id="ch5-option-2-owned-distribution-center">Option 2: Owned Distribution Center</h3>
    <p>An owned distribution center represents the largest infrastructure investment but offers maximum
      operational control. Opening an owned DC requires $300,000 in capital expenditure—equipment, facility setup,
      and systems integration. If you later close the facility, disposal and exit costs total $180,000. When
      closing an owned DC, any inventory remaining at the facility can be disposed of at 20% of its value—meaning
      you recover $0.20 for every dollar of inventory value at closing. Quarterly operating costs are lower than
      outsourced facilities at $30,000 fixed expense, reflecting your direct management rather than third-party
      margins. Inventory holding charges are also more favorable at 3% per quarter rather than 5%. The facility
      can hold finished goods inventory like outsourced centers, but critically, owned DCs enable shell
      conversion. You control operations directly, allowing you to add audio modules or control interfaces at the
      distribution center when actual demand patterns emerge. This option supports differentiation and
      postponement strategies but requires substantial upfront capital and strong volume justification.</p>
    <h2 id="ch5-part-2-dc-breakeven-analysis">Part 2: DC Breakeven Analysis</h2>
    <h3 id="ch5-outsourced-dc-breakeven">Outsourced DC Breakeven</h3>
    <p>The breakeven analysis for an outsourced distribution center in Region 2 compares costs with and without
      the facility. Operating without a DC means shipping everything via air freight from Region 1 at $22.50 per
      unit, with no other costs. Opening an outsourced DC creates a $125,000 one-time opening cost, $60,000 in
      quarterly fixed operating expenses, and changes the transportation economics. Products ship via surface
      freight to the DC at $7.50 per unit, then deliver locally to customers at another $7.50 per unit, for total
      transport costs of $15 per unit with the DC. This creates a $7.50 per unit transportation savings ($22.50
      air minus $15 surface/local).</p>
    <p>At first glance, the quarterly breakeven appears straightforward: divide the $60,000 quarterly DC cost by
      the $7.50 per unit savings to get 8,000 units per quarter. However, this simplified calculation ignores
      inventory holding charges, which significantly impact the true breakeven. Assume you maintain average
      inventory of 10,000 units at the R2 DC with a unit value of $92. The 5% quarterly holding charge on this
      inventory costs $46,000 per quarter (10,000 × $92 × 5%).</p>
    <p>The complete cost equation becomes: transportation savings must cover both DC operating costs and holding
      charges. You need ($7.50 × volume) to equal $106,000 ($60,000 operating + $46,000 holding), yielding a true
      breakeven of approximately 14,133 units per quarter. The recommendation: open an outsourced R2 DC when
      regional volume consistently exceeds 14,000-16,000 units per quarter, providing a margin of safety above
      breakeven.</p>
    <h3 id="ch5-owned-dc-breakeven">Owned DC Breakeven</h3>
    <p>Owned distribution centers have different breakeven economics due to lower operating costs and holding
      charges. See Chapter 11 for how these capital investments affect your Balance Sheet and Return on Assets
      (ROA) calculations. Above 35000, the $300,000 opening cost is double the outsourced option, but quarterly
      operating costs are only $30,000—half the outsourced rate. Transportation costs remain identical: $7.50 to
      ship to the DC plus $7.50 for local delivery, creating the same $7.50 per unit savings versus air freight
      from the plant.</p>
    <p>The simplified quarterly breakeven calculation shows 4,000 units per quarter ($30,000 ÷ $7.50), exactly
      half the outsourced breakeven due to lower operating costs. However, inventory holding charges again
      complicate the picture. Assume 15,000 units of average inventory (higher than outsourced due to shell
      storage). At 3% quarterly holding charge, this costs $41,400 per quarter (15,000 × $92 × 3%). The lower
      holding charge percentage (3% versus 5% for outsourced) partially offsets the higher inventory volume.</p>
    <p>Complete breakeven analysis: transportation savings must cover $71,400 total costs ($30,000 operating +
      $41,400 holding). This requires approximately 9,520 units per quarter. The owned DC breaks even at lower
      volume than outsourced when considering only quarterly costs, but the $300,000 opening investment requires
      longer-term justification. The recommendation: open an owned R2 DC when regional volume exceeds
      15,000-20,000 units per quarter, or when you plan extensive shell usage that requires owned facility
      capabilities. The higher upfront investment demands both volume and strategic alignment.</p>
    <h2 id="ch5-part-3-rfid-retail-channel-tagging">Part 3: RFID (Retail Channel Tagging)</h2>
    <p>All products sold through Retail channel (C1) require RFID tagging. See Chapter 8 for detailed channel
      economics and how RFID costs affect retail versus direct channel profitability. You have two options:</p>
    <h3 id="ch5-option-1-outsource-rfid">Option 1: Outsource RFID</h3>
    <p>Outsourcing RFID tagging costs $12 per unit as a pure variable expense with no upfront investment. The cost
      scales directly with retail channel volume—20,000 retail units costs $240,000, 50,000 units costs $600,000.
      This approach requires no capital commitment and avoids equipment risk if retail volumes prove lower than
      expected. The cost flows through Cost of Goods Sold as an operating expense rather than capital expenditure.
    </p>
    <h3 id="ch5-option-2-insource-rfid">Option 2: Insource RFID</h3>
    <p>Insourcing RFID involves purchasing tagging equipment for $400,000 as a capital expenditure, then operating
      it at $1 variable cost per unit. This creates a $11.00 savings per unit versus outsourcing ($12 outsourced
      minus $1 insourced). The capital investment appears on the balance sheet as Property, Plant &amp; Equipment
      and depreciates over time, while the $1 per unit cost flows through COGS as operational expense.</p>
    <p>CRITICAL REQUIREMENT: RFID insourcing is only possible if you have a distribution center (either outsourced
      or owned) already open, or if you open a DC simultaneously with the RFID investment. The tagging equipment
      must be located at a regional distribution center facility—it cannot operate from the manufacturing plant
      alone. This means you need at least one DC in any region before you can insource RFID tagging. Teams without
      any DCs must either continue outsourcing RFID or open a DC first before making the $400,000 insourcing
      investment.</p>
    <h3 id="ch5-rfid-breakeven-analysis">RFID Breakeven Analysis</h3>
    <p>The RFID insourcing breakeven calculation is straightforward: divide the $400,000 investment by the $11
      savings per unit to get 36,364 total units. Once you have tagged 36,364 retail channel units, the equipment
      has paid for itself through avoided outsourcing costs. The quarterly breakeven depends on your retail volume
      trajectory. At 50,000 retail units per quarter, payback occurs in 0.73 quarters—roughly 9 weeks. At 40,000
      units quarterly, payback extends to 0.91 quarters or 11 weeks. At 30,000 units quarterly, you are looking at
      1.21 quarters or 15 weeks to break even.</p>
    <p>The decision framework follows these volume thresholds. Above 35,000 retail units per quarter, insource
      immediately—the investment pays back in under one quarter and generates substantial savings thereafter.
      These cost savings directly improve your Operating Margin---see Chapter 11 to understand how variable cost
      reductions flow through your Income Statement. Between 25,000 and 35,000 retail units per quarter, insource
      if you are confident volume will persist or grow, as payback occurs within 1-2 quarters. Below 25,000 retail
      units quarterly, outsourcing is safer unless you have strong conviction that volumes will increase
      significantly. At this lower volume, the 1.5+ quarter payback period creates too much risk if retail demand
      disappoints.</p>
    <h2 id="ch5-part-4-cross-docking">Part 4: Cross-Docking</h2>
    <p>Cross-docking is an advanced logistics technique where incoming shipments bypass traditional warehousing
      and move directly through the distribution center to outbound transportation with minimal or no storage
      time. Instead of products arriving at a DC, sitting in inventory, and later being picked and shipped to
      customers, cross-docking routes incoming shipments directly to outgoing trucks, reducing inventory holding
      time and associated costs.</p>
    <p>In FLEXEE, cross-docking is implemented on a per-carrier basis at regional distribution centers. When you
      establish cross-docking with a carrier, products shipped via that carrier flow through the DC without
      entering long-term storage, reducing both inventory holding charges and DC operating costs. This creates
      operational efficiencies that improve your working capital management and reduce the inventory-related
      expenses that erode margins.</p>
    <h3 id="ch5-cross-docking-costs">Cross-Docking Costs</h3>
    <p>Implementing cross-docking requires both upfront investment and ongoing maintenance. The cost structure is
      carrier-specific, meaning you implement cross-docking separately for each transportation carrier you work
      with. This modular approach allows you to target your highest-volume carriers first, maximizing return on
      investment.</p>
    <p>One-Time Implementation Costs: $7,500 per carrier</p>
    <p>This initial investment covers system integration, process redesign, staff training, and coordination
      protocols between the carrier and your DC operations. The cost is paid once per carrier and applies
      regardless of which regional DC uses that carrier. Once implemented, cross-docking with that carrier is
      available at all your DCs.</p>
    <p>Ongoing Maintenance Costs: $2,500 per carrier per quarter</p>
    <p>Quarterly maintenance expenses cover system updates, ongoing coordination, performance monitoring, and
      operational support. These costs continue every quarter for each carrier with which you maintain
      cross-docking capabilities. If you work with three carriers using cross-docking, you pay $7,500 in quarterly
      maintenance ($2,500 × 3 carriers).</p>
    <h3 id="ch5-cross-docking-benefits">Cross-Docking Benefits</h3>
    <p>Cross-docking delivers two distinct financial benefits that compound to improve your distribution
      economics: reduced inventory holding charges and lower DC operating costs.</p>
    <p>Reduced Inventory Holding Charges</p>
    <p>Because products move through the DC rapidly rather than sitting in storage, inventory holding charges
      decrease significantly. The reduction depends on your DC type:</p>
    <p>Outsourced DC: 2.50% reduction in inventory charges (down from 5.00% to 2.50%)</p>
    <p>Owned DC: 1.50% reduction in inventory charges (down from 3.00% to 1.50%)</p>
    <p>These reductions apply only to inventory at DCs where cross-docking operates. The benefit scales with your
      inventory value—higher inventory levels create larger absolute savings. For example, if you maintain $1
      million in inventory at an outsourced DC using cross-docking, the quarterly holding charge drops from
      $50,000 (5%) to $25,000 (2.5%), saving $25,000 per quarter.</p>
    <p>CRITICAL LIMITATION: Cross-docking benefits do NOT apply to emergency shipments. Emergency production units
      require expedited handling that bypasses cross-docking processes, so these shipments incur standard
      inventory charges and operating costs regardless of your cross-docking investments.</p>
    <p>Reduced DC Operating Costs: $1,000 per carrier per quarter per DC</p>
    <p>Cross-docking reduces the labor, handling, and storage management required at each distribution center. For
      each carrier using cross-docking, each regional DC (excluding DC1, your home region plant) saves $1,000 in
      quarterly operating costs. This benefit multiplies across carriers and DCs: if you have cross-docking with
      two carriers operating at two regional DCs (R2 and R3), you save $4,000 per quarter ($1,000 × 2 carriers × 2
      DCs).</p>
    <h3 id="ch5-cross-docking-decision-framework">Cross-Docking Decision Framework</h3>
    <p>The cross-docking investment decision requires analyzing implementation costs against ongoing benefits,
      factoring in your inventory levels, DC structure, and carrier usage patterns.</p>
    <p>Calculate Total Costs:</p>
    <p>One-time: $7,500 per carrier</p>
    <p>Quarterly ongoing: $2,500 per carrier</p>
    <p>Example: Implementing cross-docking with one carrier costs $7,500 upfront plus $2,500 per quarter
      thereafter.</p>
    <p>Calculate Quarterly Benefits:</p>
    <p>Inventory savings: Depends on inventory value and DC type. For $800,000 average inventory at an outsourced
      DC, savings = $800,000 × 2.5% = $20,000 per quarter.</p>
    <p>Operating cost savings: $1,000 per carrier per regional DC per quarter. With one carrier and two regional
      DCs (R2, R3), savings = $1,000 × 1 carrier × 2 DCs = $2,000 per quarter.</p>
    <p>Total quarterly benefit = $20,000 + $2,000 = $22,000</p>
    <p>Breakeven Analysis:</p>
    <p>Compare one-time cost against net quarterly benefit (quarterly benefit minus ongoing maintenance).</p>
    <p>Net quarterly benefit = $22,000 - $2,500 = $19,500</p>
    <p>Breakeven = $7,500 ÷ $19,500 = 0.38 quarters (approximately 5 weeks)</p>
    <p>Strategic Considerations:</p>
    <p>High inventory operations benefit most from cross-docking. If you maintain substantial DC inventory
      (typically $500,000+ per DC), the 2.5% or 1.5% holding charge reduction creates significant savings that
      justify implementation costs quickly.</p>
    <p>Multiple carrier strategies amplify both costs and benefits. Each additional carrier adds $7,500 upfront
      and $2,500 per quarter in costs, but also adds $1,000 per DC in quarterly savings. The inventory holding
      benefit remains the same regardless of how many carriers use cross-docking.</p>
    <p>Owned DCs have lower holding charge benefits than outsourced DCs (1.5% vs 2.5%), but owned DCs typically
      carry higher inventory levels due to shell storage, which can offset the lower percentage savings. Evaluate
      based on absolute dollar savings rather than percentages alone.</p>
    <p>Emergency shipments bypass cross-docking completely, so companies relying heavily on emergency production
      should not expect cross-docking to help with those costs. Build your business case around regular production
      shipments only.</p>
    <p>When to Invest in Cross-Docking:</p>
    <p>Immediate implementation: If average inventory at DCs exceeds $600,000 and you have two or more regional
      DCs, cross-docking typically pays back in under one quarter.</p>
    <p>Strong consideration: With $400,000-$600,000 DC inventory and multiple carriers, cross-docking delivers 1-2
      quarter payback.</p>
    <p>Proceed cautiously: Below $400,000 DC inventory or with only one regional DC, the benefits may not justify
      costs unless you anticipate significant inventory growth.</p>
    <p>Start with your highest-volume carrier to maximize initial ROI, then expand to additional carriers as
      volumes justify the incremental costs.</p>
    <h2 id="ch5-chapter-summary-key-takeaways">Chapter Summary: Key Takeaways</h2>
    <ul>
      <li>DC Breakeven Is Volume-Dependent
        Outsourced DC: ~14k units/quarter breakeven. Owned DC: ~15-20k units/quarter breakeven (but enables
        Shells)</li>
      <li>Owned DC’s Add Assets → Must Justify With ROA
        $300k CapEx requires strong incremental Net Income to avoid destroying shareholder value.</li>
      <li>RFID Insourcing Breakeven: 36,364 Retail Units Total
        At 35k+ retail units/quarter, insource immediately (pays back in &lt;1 quarter). Requires a DC to be open.
      </li>
    </ul>
    <p>Cross-Docking Reduces Inventory Costs
      $7,500 setup + $2,500/quarter per carrier. Cuts holding charges in half (5%→2.5% outsourced, 3%→1.5% owned).
      Best for high-inventory operations ($600k+ at DCs). No benefit for emergency shipments.</p>
    <ul>
      <li>Shells Require Owned DCs
        If using postponement strategy, you must invest in owned DCs ($300k each).</li>
      <li>Opening Cost + Closing Cost = Commitment
        DCs are expensive to open and expensive to close. Don't open speculatively.</li>
      <li>Distribution Strategy Aligns With Competitive Positioning
        Cost leadership → outsourced, lean. Differentiation → owned, Shells. Growth → invest ahead of demand.</li>
      <li>Timing Matters: Don't Rush Into DC’s
        Wait until Q2-Q3 when volume is clear. Early DC openings drain cash and hurt ROA.</li>
    </ul>
  </section>
@endsection
