@extends('student.manual.layout')
@section('title', 'Chapter 6: Transportation Decisions')
@section('template')
  <section id="chapter-6-transportation-decisions">
    <h1>CHAPTER 6: TRANSPORTATION DECISIONS</h1>
    <div class="learning-objectives">
      <table class="doc-table">
        <tr>
          <td>
            <h2>Learning Objectives</h2>
            <p>By the end of this chapter, you will be able to:</p>
            <ul>
              <li>Understand how transportation costs are calculated in the simulation</li>
              <li>Choose air or surface shipping based on cost and urgency</li>
              <li>Understand how delayed shipments work</li>
              <li>See how transportation costs affect Product P&amp;L and Corporate P&amp;L</li>
              <li>Use transportation strategically to control costs, not reliability</li>
            </ul>
          </td>
        </tr>
      </table>
    </div>

    <h2 id="ch6-transportation-as-the-connective-tissue">Transportation As The Connective Tissue</h2>
    <p>Transportation links every node in your supply chain:</p>
    <ul>
      <li>Suppliers → Plant (procurement inbound)</li>
      <li>Plant → Distribution Centers (regional distribution)</li>
      <li>DCs → Customers (last-mile delivery)</li>
    </ul>
    <p>Transportation decisions determine:</p>
    <ul>
      <li>Delivery reliability (will products arrive on time?)</li>
      <li>Cost per unit (transport is often 5-15% of COGS)</li>
      <li>Customer satisfaction (late deliveries damage CSI)</li>
      <li>Working capital (air vs. surface affects inventory velocity)</li>
      <li>Competitive positioning (premium service requires premium transport)</li>
    </ul>
    <p><strong>Important:</strong></p>
    <p>
      In this simulation, shipments are <strong>never lost</strong>.<br>
      If a shipment is late, it simply arrives in the <strong>next quarter</strong>.
    </p>
    <h2 id="ch6-part-1-carrier-selection-plant-→-dc">Part 1: Carrier Selection (Plant → DC)</h2>
    <p>Six carriers serve the Region 2 route from your plant, each offering different cost-reliability trade-offs.
      Air shipping guarantees 100% reliability across all carriers but commands premium pricing 40-80% above
      surface rates. Surface reliability varies dramatically—from 40% for the cheapest carrier to 82% for the most
      expensive. The variance figures (±4% to ±12%) indicate delivery consistency: lower variance means more
      predictable performance. Carrier reliability directly impacts your Fill Rate and Availability Quality (AQ),
      which are key components of CSI—see Chapter 12 for how these metrics are measured.</p>
    <p>Region 2 (R2) Carriers:</p>
    <table class="doc-table">
      <tr>
        <th>Carrier</th>
        <th>Surface Cost</th>
        <th>Air Cost</th>
        <th>Surface Reliability</th>
        <th>Air Reliability</th>
      </tr>
      <tr>
        <td>I</td>
        <td>$7.50</td>
        <td>$10.00</td>
        <td>70% ± 4%</td>
        <td>100%</td>
      </tr>
      <tr>
        <td>J</td>
        <td>$5.00</td>
        <td>$12.50</td>
        <td>40% ± 8%</td>
        <td>100%</td>
      </tr>
      <tr>
        <td>K</td>
        <td>$7.50</td>
        <td>$10.00</td>
        <td>70% ± 12%</td>
        <td>100%</td>
      </tr>
      <tr>
        <td>L</td>
        <td>$10.00</td>
        <td>$12.50</td>
        <td>75% ± 4%</td>
        <td>100%</td>
      </tr>
      <tr>
        <td>M</td>
        <td>$7.50</td>
        <td>$10.00</td>
        <td>65% ± 8%</td>
        <td>100%</td>
      </tr>
      <tr>
        <td>N</td>
        <td>$12.50</td>
        <td>$15.00</td>
        <td>82% ± 12%</td>
        <td>100%</td>
      </tr>
    </table>
    <p>The cost-reliability relationship is clear: Carrier J costs $5 but delivers only 30-40% reliability, while
      Carrier N costs $15—three times more—for roughly double the reliability at 78-82%. Intermediate carriers
      like I, L, and M offer balanced trade-offs between these extremes.</p>
    <p>Region 3 (R3) Carriers</p>
    <p>Region 3 is farther from your plant, resulting in higher transportation costs across all carriers. Surface
      reliability is also generally lower for R3 due to the longer distances and more complex logistics.</p>
    <table class="doc-table">
      <tr>
        <th>Carrier</th>
        <th>Surface Cost</th>
        <th>Air Cost</th>
        <th>Surface Reliability</th>
        <th>Air Reliability</th>
      </tr>
      <tr>
        <td>I</td>
        <td>$12.50</td>
        <td>$18.00</td>
        <td>70% ± 4%</td>
        <td>100%</td>
      </tr>
      <tr>
        <td>J</td>
        <td>$5.00</td>
        <td>$18.00</td>
        <td>30% ± 8%</td>
        <td>100%</td>
      </tr>
      <tr>
        <td>K</td>
        <td>$7.50</td>
        <td>$18.00</td>
        <td>60% ± 12%</td>
        <td>100%</td>
      </tr>
      <tr>
        <td>L</td>
        <td>$7.50</td>
        <td>$18.00</td>
        <td>60% ± 4%</td>
        <td>100%</td>
      </tr>
      <tr>
        <td>M</td>
        <td>$10.00</td>
        <td>$20.00</td>
        <td>75% ± 8%</td>
        <td>100%</td>
      </tr>
      <tr>
        <td>N</td>
        <td>$15.00</td>
        <td>$22.50</td>
        <td>78% ± 12%</td>
        <td>100%</td>
      </tr>
    </table>
    <p>Key Observations:</p>
    <ul>
      <li>R3 costs are significantly higher: Surface costs range from $5-15 (vs $5-12.50 for R2), and air costs
        are $18-22.50 (vs $10-15 for R2)</li>
      <li>Reliability is generally lower: Carrier J drops from 40% (R2) to 30% (R3), and Carrier K drops from 70%
        to 60%</li>
      <li>Air freight is more uniform: Most R3 air costs cluster around $18, making carrier choice less impactful
        for air shipping</li>
    </ul>
    <p>CRITICAL GUARANTEE: Air Shipping Reliability</p>
    <p>All carriers guarantee 100% delivery reliability for air shipments, as shown in the tables above. When you
      choose air shipping for any route, your products WILL arrive at the destination—there is no delivery
      variance or risk of partial shipments. This guarantee applies to all air freight: finished goods, postponed
      production (shells), and replacement parts. The 100% air reliability makes air freight the preferred choice
      for time-sensitive shipments, high-value products, or situations where delivery certainty justifies the
      premium cost. Surface shipping, by contrast, carries delivery risk reflected in the reliability percentages
      (30-82%) and variance ranges (±4% to ±12%).</p>
    <h3 id="ch6-special-transportation-rates">Special Transportation Rates</h3>
    <p>Two product categories receive discounted transportation rates when shipping from the plant to distribution
      centers:</p>
    <p>Postponed Production (Shells): 50% of Finished Goods Transportation Cost</p>
    <p>Shells ship at half the cost of finished goods on the same route. If finished SHA units cost $7.50 to ship
      via Carrier I surface to R2, shells on the same route cost $3.75. Despite shells weighing more than finished
      units due to extra raw materials (9kg plastics + 9kg casings vs 5kg + 5kg) and including the motherboard,
      the absence of product-specific sub-assemblies and simpler packaging reduces handling complexity and
      insurance requirements, resulting in the 50% transportation discount. This reduced shipping cost improves
      the economics of postponement strategies, though you must still account for the higher inventory holding
      charges shells incur due to their extended time in the supply chain. See Chapter 4 for complete shell
      strategy analysis.</p>
    <p>Replacement Parts and Direct-to-DC Shipments:</p>
    <p>Two shipping scenarios exist for sub-assembly components: shipments from your plant to DCs, and direct
      shipments from suppliers to DCs. These have different cost structures.</p>
    <p>When shipping replacement components (audio modules, control interfaces) from the plant to distribution
      centers for shell conversion or warranty repairs, transportation costs are 50% of the finished goods rate
      and vary by region. For example, shipping 1,000 audio modules to DC3 via Carrier M surface ($10 finished
      goods rate) costs $5 per audio module, or $5,000 total. R2 shipments cost less than R3 due to distance.</p>
    <p>You can also procure sub-assemblies directly from suppliers to your distribution centers, bypassing the
      plant entirely. Suppliers ship audio modules or control interfaces straight to your owned DCs for shell
      conversion or replacement inventory. Direct-to-DC procurement has a key advantage: uniform transportation
      cost regardless of region. Shipping sub-assemblies from suppliers to DC2 costs the same as shipping to DC3.
      This flat-rate structure eliminates the distance penalty that makes R3 plant-to-DC shipping expensive, often
      making direct-to-DC the more economical choice for distant regions.
      Direct-to-DC procurement requires an owned distribution center—outsourced DCs cannot receive direct supplier
      shipments. Since shells already contain motherboards, only product-specific sub-assemblies (audio modules
      for SHA conversion, control interfaces for ST conversion) need to ship to DCs for conversion.</p>
    <h2 id="ch6-part-2-exclusive-carrier-contracts-rebates">Part 2: Exclusive Carrier Contracts &amp; Rebates</h2>
    <p>FLEXEE offers a 20% rebate on transportation charges if you use a carrier exclusively for a given route.
    </p>
    <p>How Exclusivity Works</p>
    <p>The exclusive carrier contract mechanism offers a straightforward value proposition: commit to using only
      one carrier for Plant → R2 and only one carrier for Plant → R3 (they can be the same or different carriers)
      and receive a 20% rebate on all transportation charges. The rebate applies to shipping costs, not
      reliability improvements.</p>
    <p>Consider a scenario where you ship 20,000 units to Region 2. Without exclusivity, you might split volume
      between Carrier I (10,000 units at $7.50) and Carrier M (10,000 units at $7.50), generating $150,000 in
      total costs with no rebate. With exclusivity, consolidating all 20,000 units on Carrier I still costs
      $150,000 in base charges, but the 20% rebate returns $30,000, reducing net costs to $120,000. This $30,000
      savings—a 20% cost reduction—requires no operational changes beyond carrier consolidation. The trade-off
      involves concentrating risk: if your exclusive carrier experiences disruptions, you have no backup surface
      option and must resort to emergency air freight.</p>
    <h3 id="ch6-emergency-carrier-designation">Emergency Carrier Designation</h3>
    <p>For each regional distribution center (DC2 in R2 and DC3 in R3), you must designate an emergency carrier to
      handle urgent shipments when regular carrier capacity is insufficient or when emergency production requires
      expedited delivery. This designation does not require exclusive contracts or generate additional costs—it
      simply establishes which carrier will receive emergency volume when needed.</p>
    <p>Emergency shipments automatically route via air freight regardless of your normal shipping mode
      preferences, ensuring 100% delivery reliability. The emergency carrier you designate should ideally be one
      you already use for regular shipments to that region, simplifying logistics coordination and potentially
      supporting exclusive carrier contract requirements. However, you can designate any carrier from the
      available options for each region.</p>
    <p>Strategic Considerations:</p>
    <p>If using exclusive carrier contracts, designate the same carrier for emergency use to maintain
      single-carrier relationships and avoid complicating rebate eligibility.</p>
    <p>Emergency carriers rarely receive significant volume under normal operations. The designation matters
      primarily during supply chain disruptions, emergency production activations, or late-quarter rush scenarios.
    </p>
    <p>Since all carriers offer 100% air reliability, the emergency carrier choice primarily impacts cost rather
      than service level. Choose carriers with competitive air rates for your emergency designation to minimize
      expedited shipping expenses when emergencies occur.</p>
    <h2 id="ch6-part-3-customer-shipping-dc-→-customer">Part 3: Customer Shipping (DC → Customer)</h2>
    <p>Customer shipping costs from distribution centers to end customers vary by region and channel, with direct
      channel shipping consistently costing double the retail channel due to individual consumer shipments versus
      bulk retailer deliveries.</p>
    <h3 id="ch6-with-distribution-center-surface-shipping">With Distribution Center (Surface Shipping):</h3>
    <table class="doc-table">
      <tr>
        <th>Region</th>
        <th>C1 (Retail)</th>
        <th>C2 (Direct)</th>
      </tr>
      <tr>
        <td>R1</td>
        <td>$5.00</td>
        <td>$10.00</td>
      </tr>
      <tr>
        <td>R2</td>
        <td>$7.50</td>
        <td>$15.00</td>
      </tr>
      <tr>
        <td>R3</td>
        <td>$10.00</td>
        <td>$20.00</td>
      </tr>
    </table>
    <h3 id="ch6-without-distribution-center-air-from-plant">Without Distribution Center (Air from Plant):</h3>
    <table class="doc-table">
      <tr>
        <th>Region</th>
        <th>C1 (Retail)</th>
        <th>C2 (Direct)</th>
      </tr>
      <tr>
        <td>R2</td>
        <td>$22.50</td>
        <td>$35.00</td>
      </tr>
      <tr>
        <td>R3</td>
        <td>$32.50</td>
        <td>$45.00</td>
      </tr>
    </table>
    <p>The air freight premiums—ranging from 200% to 350% above DC-based surface shipping—make distribution
      centers economically compelling at relatively modest volumes of 10,000-15,000 units per quarter.</p>
    <h3 id="ch6-total-transportation-cost-example">Total Transportation Cost Example (Plant to Customer)</h3>
    <p>Understanding the complete transportation journey from plant to customer helps clarify the economic
      advantages of distribution centers. The table below shows total transportation costs for the entire supply
      chain using Carrier I as an example, illustrating how plant-to-DC costs combine with DC-to-customer costs.
    </p>
    <p>Sample Total Transportation Costs (Using Carrier I):</p>
    <table class="doc-table">
      <tr>
        <th>Region</th>
        <th>Channel 1 (Retail)<br />Surface via Local DC</th>
        <th>Channel 1 (Retail)<br />Air to DC</th>
        <th>Channel 2 (Direct)<br />Surface via Local DC</th>
        <th>Channel 2 (Direct)<br />Air to DC</th>
      </tr>
      <tr>
        <td>R1</td>
        <td>$5.00<br />(local only)</td>
        <td>—</td>
        <td>$10.00<br />(local only)</td>
        <td>—</td>
      </tr>
      <tr>
        <td>R2</td>
        <td>$15.00<br />($7.50 + $7.50)</td>
        <td>$17.50<br />($10.00 + $7.50)</td>
        <td>$22.50<br />($7.50 + $15.00)</td>
        <td>$25.00<br />($10.00 + $15.00)</td>
      </tr>
      <tr>
        <td>R3</td>
        <td>$22.50<br />($12.50 + $10.00)</td>
        <td>$28.00<br />($18.00 + $10.00)</td>
        <td>$32.50<br />($12.50 + $20.00)</td>
        <td>$38.00<br />($18.00 + $20.00)</td>
      </tr>
    </table>
    <p>Cost Breakdown Explanation:</p>
    <p>Region 1 requires only local delivery since the plant is located in R1. Products move directly from the
      plant to customers at $5.00 (retail) or $10.00 (direct) per unit.</p>
    <p>Region 2 with surface shipping via DC totals $15.00 for retail: $7.50 to ship from plant to R2 DC using
      Carrier I surface freight, plus $7.50 for local delivery from DC to retail customers. The direct channel
      totals $22.50: the same $7.50 plant-to-DC cost plus $15.00 for DC-to-customer delivery.</p>
    <p>Region 2 with air shipping to DC costs more but delivers faster and more reliably. Retail totals $17.50:
      $10.00 for air freight from plant to R2 DC using Carrier I, plus $7.50 for local delivery. Direct totals
      $25.00: $10.00 air freight plus $15.00 DC-to-customer.</p>
    <p>Region 3 demonstrates higher costs due to distance. Surface via DC costs $22.50 retail ($12.50 plant-to-DC
      + $10.00 DC-to-customer) and $32.50 direct ($12.50 + $20.00). Air to DC costs $28.00 retail ($18.00 +
      $10.00) and $38.00 direct ($18.00 + $20.00).</p>
    <p>Strategic Insight: These total costs reveal why distribution centers become essential at scale. Without a
      DC, R2 retail shipping costs $22.50 per unit via air from the plant (see previous table). With a DC using
      surface freight, the same delivery costs only $15.00—a $7.50 savings per unit, or 33% cost reduction. At
      20,000 units per quarter, this single decision saves $150,000 quarterly, easily justifying DC investments.
      The economics become even more compelling for R3, where the DC saves $10.00 per retail unit ($32.50 air from
      plant vs $22.50 surface via DC).</p>
    <h2 id="ch6-chapter-summary-key-takeaways">Chapter Summary: Key Takeaways</h2>
    <ul>
      <li>Carrier Choice Is Total Cost Of Ownership
        Cheapest carrier often costs MORE when delays trigger emergency air freight. Calculate TCO, not just base
        rate.</li>
      <li>70-75% Surface Reliability Is Sweet Spot
        Carriers I, L, M offer best cost-reliability balance. Avoid extremes.</li>
      <li>Exclusive Contracts = 20% Rebate
        "Free" savings if carrier is reliable. Commit to one carrier per region when volume &gt;15k and
        reliability &gt;70%.</li>
      <li>Air Guarantees Delivery But Costs 40-80% More
        Use air selectively: critical shipments, high-margin products, late-quarter urgency. All air shipments
        have 100% delivery guarantee.</li>
    </ul>
    <p>Shells & Replacement Parts: Replacement components are transported under the same cost structure as
      finished goods. For example, shipment from Shell to R2 via Carrier I incurs a transport cost of $7.50,
      consistent with the FGI rate.</p>
    <ul>
      <li>Direct Channel Costs 2× Retail For Transport
        Direct = $10-20/unit (with DC), Retail = $5-10/unit. Direct requires higher prices to cover costs.</li>
      <li>Transport $/Unit Is Key KPI
        Target &lt;$12 per unit across all shipments.</li>
      <li>Without DCs, R2/R3 Transport Is Prohibitive
        Air from plant: $22.50-45/unit. DC surface: $7.50-20/unit. DCs pay for themselves quickly on transport
        alone.</li>
    </ul>
  </section>
@endsection
