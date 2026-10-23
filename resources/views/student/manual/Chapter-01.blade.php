@extends('student.manual.layout')
@section('title', 'Chapter 1: Introduction')
@section('template')
  <section id="chapter-1-introduction">
    <h1>CHAPTER 1: INTRODUCTION</h1>
    <div class="learning-objectives">
      <table class="doc-table">
        <tr>
          <td>
            <h2>Learning Objectives:</h2>
            <p>By the end of this chapter, you will be able to:</p>
            <ul>
              <li>Understand the scope and structure of the FLEXEE simulation</li>
              <li>Explain how supply chain decisions create competitive advantage or disadvantage</li>
              <li>Describe the interconnections between procurement, production, distribution, and finance</li>
              <li>Identify key performance dimensions: Financial, Operational, Customer, and Investor</li>
              <li>Recognize common supply chain trade-offs and their consequences</li>
            </ul>
          </td>
        </tr>
      </table>
    </div>

    <h2 id="ch1-what-is-flexee">What Is FLEXEE?</h2>
    <p>FLEXEE (Flexible Learning Environment for Experiential Education) is a comprehensive supply chain
      management simulation that places you in the role of a supply chain manager for a consumer electronics firm.
    </p>
    <p>As supply chain manager, you control every major supply chain decision across eight functional areas. In
      procurement, you determine which suppliers to use, order quantities, and shipping methods. Production
      decisions include unit quantities, choosing between regular and emergency production, and whether to build
      shells or finished goods. You decide distribution strategy: where to locate distribution centers and whether
      to own or outsource them. Transportation choices involve selecting carriers, choosing between air and
      surface shipping, and negotiating exclusive contracts. You set customer service levels, manage demand
      through pricing and marketing decisions and product introductions, forecast future demand, and decide which
      supply chain partners to integrate with through enterprise IT systems.</p>
    <p>Every decision connects to others, every choice involves trade-offs, and every quarter reveals the
      financial and operational consequences of your decisions as you adapt your strategy.</p>
    <h2 id="ch1-the-world-you-compete-in">The World You Compete In</h2>
    <p>Industry: Consumer Electronics</p>
    <p>You produce two products in the consumer electronics category. The Smart Home Assistant (SHA) is a
      voice-activated device for home automation, similar to products like Amazon Echo or Google Home. The Smart
      Thermostat (ST) is a programmable climate control device with learning capabilities, comparable to the Nest
      Thermostat or Ecobee.</p>
    <p>Both products share some components, creating economies of scale while also creating shared risks in your
      supply chain. Raw materials (plastics and casings) and motherboards are identical across both product lines.
      The key difference lies in the sub-assemblies: the SHA requires an Audio Module (AM) for voice interaction,
      while the ST requires a Control Interface (CI) for climate control functionality. This component overlap
      means procurement and production decisions for one product directly impact the other. See Chapter 3 for
      volume discount strategies that leverage this shared component structure.</p>
    <p>Geographic Scope: Three Regions</p>
    <p>Your market spans three distinct regions with different operational characteristics. Region 1 (R1) is your
      home region where the manufacturing plant is located, providing the lowest transportation costs and serving
      as your test market. Region 2 (R2) is an adjacent region with moderate transport costs, representing a
      natural expansion opportunity. Region 3 (R3) is a distant region with high transport costs, requiring
      careful analysis before market entry.</p>
    <h2 id="ch1-sales-channels-retail-vs-direct">Sales Channels: Retail vs. Direct</h2>
    <p>You can reach customers through two distinct channels, each with different economics and strategic
      implications. The retail channel (Channel 1) involves selling to major retailers like Best Buy and Walmart,
      who then resell to end customers. You set the wholesale price while retailers add their markup for
      consumers. This channel requires RFID tagging, which you can either outsource at $12 per unit or insource
      for a $400,000 capital investment plus $1 per unit. Retail offers lower order fulfillment costs at $4 per
      unit due to bulk shipments and provides higher volume potential.</p>
    <p>The direct channel (Channel 2) allows you to sell directly to consumers through online sales, giving you
      complete control over the final price. This channel carries higher order fulfillment costs at $24 per unit
      due to individual shipments but requires no RFID tagging. Direct sales offer higher margin potential per
      unit but typically generate lower overall volume than retail.</p>
    <h2 id="ch1-the-flexee-scorecard-how-you-are-evaluated">The Flexee Scorecard: How You are Evaluated</h2>
    <p>Financial Performance measures your profitability and growth through four key metrics. Net Profit Margin
      reveals whether you are keeping enough of each revenue dollar after all expenses. Operating Margin shows how
      efficiently you manage costs before interest and taxes. Return on Assets (ROA) indicates how productively
      you are using your asset base. Sales Growth tracks whether revenue is increasing quarter-over-quarter,
      signaling market traction and business momentum.</p>
    <p>Operational Excellence evaluates your supply chain efficiency and quality across seven dimensions.
      Inventory Turnover measures how efficiently you manage inventory levels and working capital. Fill Rate
      tracks your ability to fulfill customer orders immediately from stock. Forecast Accuracy reveals how well
      you predict demand, which directly impacts planning effectiveness. Production Deviation shows your reliance
      on expensive emergency production. Defect Rate indicates how many units are failing quality
      standards.Controllable Costs to Revenue measures your discretionary operational spending as a percentage of
      sales, penalizing inefficient use of emergency measures and poor planning discipline.Transport cost per unit
      measures your logistics spending efficiency.</p>
    <p>Customer Success focuses on your market position and customer satisfaction through two critical measures.
      Market Penetration reveals what share of total industry sales you are capturing relative to competitors. The
      Customer Satisfaction Index (CSI) aggregates customer happiness across product quality, service quality, and
      product availability, serving as a leading indicator of future demand.</p>
    <p>Investor Attractiveness is captured in the FI Index (Financial Investor Index), a weighted composite score
      from 0 to 96 that combines profitability (30%), growth (30%), consistency (20%), and efficiency (20%). The
      consistency component uses binary scoring that rewards three consecutive quarters of Net Income growth. This
      single metric represents how attractive your business is to potential investors, balancing short-term
      performance with long-term sustainability.Higher FI Index scores indicate stronger investment
      attractiveness.</p>
    <h2 id="ch1-chapter-summary-key-takeaways">Chapter Summary: Key Takeaways</h2>
    <ul>
      <li>FLEXEE is a supply chain simulation where you manage a consumer electronics company competing in a
        realistic global market with actual supply chain decisions and financial consequences.</li>
      <li>You compete in three regions (R1, R2, R3) with different demand levels, costs, and competitive dynamics.
        Each region requires strategic positioning decisions.</li>
      <li>Two sales channels require different strategies: Retail (C1) uses retailers and requires RFID tagging
        with $4 fulfillment costs. Direct (C2) sells online with $24 fulfillment costs but no RFID requirement.
      </li>
      <li> The FLEXEE Scorecard measures performance through 14 KPIs across four perspectives: Financial (Net
        Profit Margin, Operating Margin, ROA, Sales Growth), Operational (Inventory Turnover, Fill Rate, Forecast
        Accuracy, Production Deviation, Defect Rate, Controllable Costs/Revenue, Transport $/Unit), Customer
        (Market Penetration, CSI), and Investor (FI Index). All metrics matter—there is no single "winning"
        metric. </li>
      <li>Decisions span the entire supply chain: Procurement, manufacturing, distribution, transportation,
        service, and demand management all interact. Optimizing one area in isolation often hurts overall
        performance.</li>
      <li>Strategic coherence beats local optimization: The best performers align all decisions around a clear
        strategy (cost leadership or differentiation), rather than making disconnected tactical choices.</li>
      <li>Financial accounting matters: Every operational decision flows through to the income statement, balance
        sheet, and cash flow statement. Understanding these connections is essential for success.</li>
    </ul>
  </section>
@endsection
