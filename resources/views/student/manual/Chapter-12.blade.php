@extends('student.manual.layout')
@section('title', 'Chapter 12: Performance Evaluation')
@section('template')
  <section id="chapter-12-performance-evaluation">
    <h1>CHAPTER 12: PERFORMANCE EVALUATION</h1>
    <div class="learning-objectives">
      <table class="doc-table">
        <tr>
          <td>
            <h2>Learning Objectives:</h2>
            <p>By the end of this chapter, you will be able to:</p>
            <ul>
              <li>Understand the balanced scorecard structure, including Financial, Operational, Customer, and
                Investor perspectives</li>
              <li>Calculate key performance indicators (KPIs) and understand their assigned weights</li>
              <li>Interpret the Financial Investor (FI) Index</li>
              <li>Align operational decisions with balanced scorecard optimization</li>
              <li>Balance competing metrics to achieve overall performance goals</li>
            </ul>
          </td>
        </tr>
      </table>
    </div>

    <h2 id="ch12-the-balanced-scorecard">The Balanced Scorecard</h2>
    <p>FLEXEE evaluates performance across four integrated perspectives that together measure business health. The
      financial perspective assesses profitability and growth through metrics like margins and sales increases.
      The operational perspective evaluates efficiency and quality across supply chain activities including
      inventory management, forecasting, and defect control. The customer perspective captures market position and
      satisfaction through penetration rates and the Customer Satisfaction Index. Finally, the investor
      perspective aggregates overall business attractiveness through the FI Index, a composite measure balancing
      profitability, growth, consistency, and efficiency.</p>
    <h2 id="ch12-part-1-financial-kpis">Part 1: Financial KPIs</h2>
    <p>These financial metrics are outcomes of decisions made in procurement (Chapter 3), production (Chapter 4),
      distribution (Chapter 5), and pricing/marketing (Chapter 8)---see Chapter 11 for how to read these metrics
      on your financial statements.</p>
    <table class="doc-table">
      <tr>
        <th>KPI</th>
        <th>Weight</th>
        <th>Formula</th>
        <th>Healthy Range</th>
      </tr>
      <tr>
        <td>Net Profit Margin</td>
        <td>3</td>
        <td>Net Income ÷ Revenue × 100</td>
        <td>8-15%</td>
      </tr>
      <tr>
        <td>Operating Margin</td>
        <td>1</td>
        <td>Operating Income ÷ Revenue × 100</td>
        <td>12-20%</td>
      </tr>
      <tr>
        <td>Return on Assets (ROA)</td>
        <td>2</td>
        <td>(Net Income ÷ Total Assets) × 4</td>
        <td>12-25%</td>
      </tr>
      <tr>
        <td>Sales Growth</td>
        <td>1</td>
        <td>(Q Revenue - Prior Q) ÷ Prior Q × 100</td>
        <td>5-15%</td>
      </tr>
    </table>
    <p>Total Financial Weight: 7</p>
    <h2 id="ch12-part-2-operational-kpis">Part 2: Operational KPIs</h2>
    <p>Positive KPIs (Higher = Better):</p>
    <table class="doc-table">
      <tr>
        <th>KPI</th>
        <th>Weight</th>
        <th>Formula</th>
        <th>Target</th>
      </tr>
      <tr>
        <td>Inventory Turnover</td>
        <td>2</td>
        <td>COGS ÷ Avg Inventory × 4 (annualized)</td>
        <td>20-32 per year (5-8 per quarter)</td>
      </tr>
      <tr>
        <td>Fill Rate</td>
        <td>1</td>
        <td>Orders filled ÷ Orders received × 100</td>
        <td>95-98%</td>
      </tr>
      <tr>
        <td>Forecast Accuracy</td>
        <td>2</td>
        <td>100 × (1 - |Forecast - Actual| ÷ Actual)</td>
        <td>90-95%</td>
      </tr>
    </table>
    <p>Negative KPIs (lower = better):</p>
    <table class="doc-table">
      <tr>
        <th>KPI</th>
        <th>Weight</th>
        <th>Formula</th>
        <th>Target</th>
      </tr>
      <tr>
        <td>Production Deviation</td>
        <td>-1</td>
        <td>Emergency prod ÷ Total prod × 100</td>
        <td>&lt;5%</td>
      </tr>
      <tr>
        <td>Defect Rate</td>
        <td>-1</td>
        <td>(Replacement Parts ÷ Units Sold) × 100</td>
        <td>&lt;2%</td>
      </tr>
      <tr>
        <td>Controllable Costs/Revenue</td>
        <td>-1</td>
        <td>(Controllable Costs ÷ Revenue) × 100</td>
        <td>&lt;15%</td>
      </tr>
      <tr>
        <td>Transport $/Unit</td>
        <td>-1</td>
        <td>Transport costs ÷ Units sold</td>
        <td>&lt;$12</td>
      </tr>
    </table>
    <p>For the purposes of the Operational KPI calculation Controllable Costs include 5 categories: Emergency
      Procurement premiums, Emergency Production premiums, Inventory Holding Charges, Procurement Fixed Costs, and
      Production Fixed Costs. This definition captures the key discretionary operational costs that reflect supply
      chain planning quality and operational discipline. The KPI penalizes reactive management that relies on
      expensive emergency mechanisms and inefficient operations. Keep controllable costs below 15% of revenue
      through proactive planning, accurate forecasting, and efficient operations.</p>
    <p>Total Operational Weight: 9 (Net: 5 positive - 4 negative = 1)</p>
    <h2 id="ch12-part-3-customer-kpis">Part 3: Customer KPIs</h2>
    <table class="doc-table">
      <tr>
        <th>KPI</th>
        <th>Weight</th>
        <th>Formula</th>
        <th>Target</th>
      </tr>
      <tr>
        <td>Market Penetration</td>
        <td>1</td>
        <td>Your sales ÷ Industry sales × 100</td>
        <td>15-25%</td>
      </tr>
      <tr>
        <td>CSI</td>
        <td>2</td>
        <td>(0.4 × Fill Rate) + (0.3 × Quality) + (0.2 × Forecast Acc.) + (0.1 × Price Score)</td>
        <td>85-95</td>
      </tr>
    </table>
    <p>Total Customer Weight: 3</p>
    <p>Customer Satisfaction Index (CSI) Formula:</p>
    <p><strong>CSI = (0.4 × Fill Rate) + (0.3 × Quality Score) + (0.2 × Forecast Accuracy) + (0.1 × Price
        Score)</strong></p>
    <p>CSI is driven by Fill Rate, Quality Score, Forecast Accuracy, and Price Score.</p>
    <p>The CSI is a weighted composite score (0-100) that approximates customer satisfaction using operational
      KPIs. All components are on a 0-100 scale:</p>
    <ul>
      <li><strong>Fill Rate (40% weight)</strong> — Percent of customer demand fulfilled from available inventory.
        Measures product availability. Higher is better.</li>
      <li><strong>Quality Score (30% weight) = 100 − Defect Rate %</strong> — Product quality inferred from defect
        rate. A 4% defect rate yields Quality Score = 96. Higher is better.</li>
      <li><strong>Forecast Accuracy (20% weight)</strong> — How close forecasts were to actual demand. Drives
        product availability and inventory efficiency. Higher is better.</li>
      <li><strong>Price Score (10% weight) = max(0, 100 − Price Gap %)</strong> — Price competitiveness relative
        to market average. Price Gap % = ((Your ASP − Market ASP) ÷ Market ASP) × 100. If your price equals market
        average, Price Score = 100. If your price is higher than market, score decreases.</li>
    </ul>
    <p>Example: Fill Rate = 92, Defect Rate = 4 (Quality Score = 96), Forecast Accuracy = 85, Price Score = 95 →
      CSI = (0.4 × 92) + (0.3 × 96) + (0.2 × 85) + (0.1 × 95) = 36.8 + 28.8 + 17.0 + 9.5 = 92</p>
    <p>The weighting prioritizes fill rate and quality, reflecting that customers most value product availability
      and product quality. CSI drives future demand growth. High CSI (90+) compounds over quarters. Fill Rate is
      influenced by forecasting accuracy (Chapter 9) and inventory management (Chapters 3-4). Quality Score is
      influenced by supplier selection (Chapter 3) and warranty choices (Chapter 2). Price Score reflects pricing
      decisions (Chapter 8) relative to market competition.</p>

    <h2 id="ch12-part-4-external-kpis">Part 4: External KPIs – Investor Perspective</h2>
    <p> These KPIs assess your firm's attractiveness to external financial investors
      by evaluating growth performance and stability. They are <b>leading indicators</b>
      of future valuation and influence how the market perceives your company’s
      long-term potential. Together, they account for a total weight of 3 in the
      FLEXEE scorecard.
    </p>
    <table class="doc-table">
      <tr>
        <th>KPI</th>
        <th>Weight</th>
        <th>Formula</th>
        <th>Target</th>
      </tr>

      <tr>
        <td>Revenue Growth</td>
        <td>1</td>
        <td>(Current Q Revenue − Prior Q Revenue) ÷ Prior Q Revenue × 100</td>
        <td>5–15%</td>
      </tr>

      <tr>
        <td>Earnings Growth</td>
        <td>1</td>
        <td>(Current Q Net Income − Prior Q Net Income) ÷ Prior Q Net Income × 100</td>
        <td>10–20%</td>
      </tr>

      <tr>
        <td>Consistency Score</td>
        <td>1</td>
        <td>Binary: 1 if Net Income increased for 3+ consecutive quarters, else 0</td>
        <td>1.0</td>
      </tr>
    </table>
    <p><b>Interpretation:</b></p>

    <ul>
      <li><b>High revenue and earnings growth</b> signal expansion and profitability momentum.</li>
      <li><b>Consistency</b> indicates reliability — a key trait valued by long-term investors.</li>
    </ul>
    <h2 id="ch12-part-5-financial-investor-fi-index">Part 5: Financial Investor (FI) Index</h2>
    <p>FI Index = Composite measure of attractiveness to investors</p>
    <p>FI Index = 0.30 × Profitability + 0.30 × Growth + 0.20 × Consistency + 0.20 × Efficiency

    <p><strong>IMPORTANT:</strong> The FI Index uses raw metric values directly in the weighted formula.</p>
    <ul>
      <li>Profitability = Net Profit Margin value (e.g., 26.01)</li>
      <li>Growth = Sales Growth value (e.g., 11.97)</li>
      <li>Consistency = Binary score (1.0 or 0.0)</li>
      <li>Efficiency = Inventory Turnover value (e.g., 30.41)</li>
    </ul>
    <p>Example calculation: If NPM=26.01, Sales Growth=11.97, Consistency=1.0, Inventory Turnover=30.41:<br />
      FI Index = 0.30(26.01) + 0.30(11.97) + 0.20(1.0) + 0.20(30.41) = 17.67</p>
    </p>
    <p>FI Index scores translate into investment recommendations based on relative performance. Higher scores
      indicate stronger investment attractiveness. Scores of 80+ indicate excellent performance, marking the
      company as a strong investment target with superior profitability, growth, consistency, and efficiency.
      Scores of 60-80 represent good performance that attracts investors seeking solid returns. The 40-60 range
      signals fair performance with neutral investment appeal—neither particularly attractive nor problematic.
      Scores of 20-40 indicate weak performance that sophisticated investors avoid. Finally, scores below 20
      represent poor performance suggesting divestment as the appropriate action.</p>


    <h2 id="ch12-chapter-summary-key-takeaways">Chapter Summary: Key Takeaways</h2>
    <ul>
      <li>Balanced Scorecard Has Four Perspectives
        Financial (7 weight), Operational (5 positive, 4 negative; net weight = 1), Customer (3 weight), Investor
        (FI
        Index)</li>
      <li>Net Profit Margin Is Most Heavily Weighted (3)
        Focus on bottom-line efficiency. Control COGS and OpEx.</li>
      <li>Inventory Turnover &amp; Forecast Accuracy Tied (2 Each)
        Operational excellence requires lean inventory AND accurate planning.</li>
      <li>CSI Drives Future Demand (Weight 2)
        Invest in Fill Rate, Quality Score, Forecast Accuracy, and Price discipline. High CSI (90+) compounds over
        quarters. High CSI (90+) compounds over quarters.</li>
      <li> Seven Operational KPIs Measure Supply Chain Performance Inventory Turnover & Forecast Accuracy are most
        heavily weighted (2 each). Four negative KPIs (Production Deviation, Defect Rate, Controllable Costs to
        Revenue, Transport $/Unit) create discipline.</li>
      <li>Negative KPI's Penalize Poor Practices Emergency production, defects, controllable costs, and high
        transport
        costs hurt scores.</li>
      <li>FI Index Balances Four Dimensions
        30% Profitability, 30% Growth, 20% Consistency (binary: 3 consecutive quarters of Net Income growth), 20%
        Efficiency.</li>
      <li>Trade-Offs Are Inevitable
        Growth vs. Profit, Efficiency vs. Quality, Cost vs. Service. Balance is key.</li>
      <li>Strategy Must Be Coherent
        Cost leadership, differentiation, or growth—align all KPIs with chosen strategy.</li>
    </ul>
  </section>
@endsection
