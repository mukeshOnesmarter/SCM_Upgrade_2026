@extends('student.manual.layout')
@section('title', 'Chapter 9: Forecasting Decisions')
@section('template')
  <section id="chapter-9-forecasting">
    <h1>CHAPTER 9: FORECASTING</h1>
    <div class="learning-objectives">
      <table class="doc-table">
        <tr>
          <td>
            <h2>Learning Objectives:</h2>
            <p>By the end of this chapter, you will be able to:</p>
            <ul>
              <li>Understand how forecast accuracy directly affects administrative overhead costs</li>
              <li>Apply forecasting methods appropriate for FLEXEE’s quarterly structure</li>
              <li>Recognize demand patterns and adjust forecasts accordingly</li>
              <li>Calculate forecast accuracy and evaluate its financial impact</li>
              <li>Balance forecast optimism with accuracy requirements</li>
            </ul>
          </td>
        </tr>
      </table>
    </div>

    <h2 id="ch9-forecasting-as-the-planning-foundation">Forecasting As The Planning Foundation</h2>
    <p>Every supply chain decision starts with a forecast. Forecast accuracy affects procurement planning (Chapter
      3), production scheduling (Chapter 4), distribution requirements (Chapter 5), and product introduction
      timing (Chapter 8).</p>
    <ul>
      <li>Procurement: How many components to order?</li>
      <li>Production: How many units to build?</li>
      <li>Distribution: Where to position inventory?</li>
      <li>Demand Management: How much marketing to invest?</li>
    </ul>
    <p>Poor forecasting cascades:</p>
    <ul>
      <li>Underforecast → Stockouts → Emergency procurement/production → Cost spike → Margin erosion → CSI damage
      </li>
      <li>Overforecast → Excess inventory → Holding charges → Cash tied up → Working capital problems</li>
    </ul>
    <p>In FLEXEE, forecast accuracy directly affects administrative overhead: Every 1% error adds 1% overhead (max
      doubling).</p>
    <p>Forecast Accuracy Formula &amp; Impact</p>
    <p>Forecast Accuracy = 100 × (1 - |Forecast - Actual| ÷ Actual)</p>
    <p>Examples:</p>
    <p>• Forecast 90k, Actual 100k: Accuracy = 100 × (1 - 10k/100k) = 90%</p>
    <p>• Forecast 16k, Actual 8k: Accuracy = 100 × (1 - 8k/8k) = 0%</p>
    <p>• Forecast 105k, Actual 100k: Accuracy = 100 × (1 - 5k/100k) = 95%</p>
    <p>Administrative Overhead Impact:</p>
    <p>Base overhead (Retail): $80k per product/quarter
      Base overhead (Direct): $120k per product/quarter</p>
    <p>Adjusted overhead = Base × (1 + Error %)</p>
    <p>Example 1: 90% Accuracy (10% error)</p>
    <p>• Retail overhead: $80k × 1.10 = $88k (+$8k)
      • Direct overhead: $120k × 1.10 = $132k (+$12k)</p>
    <p>Example 2: 50% Accuracy (50% error, capped at 100% increase)</p>
    <p>• Retail overhead: $80k × 2.0 = $160k (+$80k penalty)
      • Direct overhead: $120k × 2.0 = $240k (+$120k penalty)</p>
    <p>Over 8 quarters, poor forecasting (70% accuracy avg) costs ~$500k+ in excess overhead.</p>
    <h2 id="ch9-forecasting-methodology-–-simple-methods">Forecasting Methodology – Simple Methods</h2>
    <p>Three simple forecasting methods provide reliable starting points for quarterly demand planning. The naive
      forecast simply uses the current quarter's actual demand as the next quarter's forecast. If Q2 actual demand
      was 50,000 units, the Q3 forecast becomes 50,000 units. This method works well for stable markets where
      demand fluctuates minimally quarter to quarter.</p>
    <p>The moving average forecast smooths short-term volatility by averaging recent quarters. Using a two-quarter
      moving average, if Q2 demand was 48,000 units and Q3 was 52,000 units, the Q4 forecast becomes 50,000
      units—the average of the two most recent quarters. This approach filters random noise while remaining
      responsive to genuine demand shifts.</p>
    <p>Trend adjustment forecasts capture momentum by applying observed growth rates to the most recent quarter.
      If Q2 demand was 50,000 units and Q3 grew to 55,000 units (10% growth), the Q4 forecast projects 60,500
      units (55,000 × 1.10). This method works best when clear growth or decline patterns have emerged over
      multiple quarters.</p>
    <p>The recommended approach varies by simulation stage. Quarter 1 requires conservative estimates since no
      historical data exists—forecast 80-90% of optimistic projections to build safety margin. Quarters 2-3 favor
      naive or moving average methods during the learning phase as you establish baseline patterns. Quarter 4 and
      beyond benefit from trend-adjusted forecasts once sustained growth or decline patterns become evident.</p>
    <h2 id="ch9-common-forecasting-mistakes">Common Forecasting Mistakes</h2>
    <p>Four forecasting errors consistently undermine planning accuracy. The first mistake involves forecasting
      for growth rather than accuracy—the trap of declaring "I want 100,000 sales, so I'll forecast 100,000."
      Instead, forecast the 80,000 units you will actually sell, then build for 85,000 with safety stock. This
      achieves 94% forecast accuracy rather than 20% accuracy from wishful thinking.</p>
    <p>The second error ignores replacement demand generated by defects. A 2% defect rate on 50,000 units creates
      1,000 replacement units that must be included in production planning. Forgetting replacement demand causes
      unexpected stockouts even when primary forecasts prove accurate.</p>
    <p>Large forecast swings between quarters create the third problem. Jumping from a 50,000 unit Q2 forecast to
      an 80,000 unit Q3 forecast (60% increase) exceeds procurement's response capability, especially for
      motherboards requiring one-quarter lead times. Smooth forecasts even when expecting growth—project 50,000 to
      60,000 to 70,000 rather than dramatic jumps that supply chains cannot accommodate.</p>
    <p>The final mistake fails to reconcile top-down and bottom-up forecasts. Top-down analysis might project 20%
      market share of a 500,000 unit market, suggesting 100,000 unit forecast. Bottom-up regional forecasts sum to
      75,000 units (R1: 30,000 + R2: 25,000 + R3: 20,000). This 25,000 unit gap demands investigation: which
      methodology is more reliable? Adjust accordingly rather than ignoring the discrepancy and hoping it resolves
      itself.</p>
    <h2 id="ch9-forecast-accuracy-targets">Forecast Accuracy Targets</h2>
    <table class="doc-table">
      <tr>
        <th>Accuracy Level</th>
        <th>Range</th>
        <th>Overhead Impact</th>
        <th>Operational Efficiency</th>
        <th>Strategy</th>
      </tr>
      <tr>
        <td>EXCELLENT</td>
        <td>95%+</td>
        <td>Minimal (+5% = $4k-6k)</td>
        <td>High</td>
        <td>Stable markets, conservative estimates</td>
      </tr>
      <tr>
        <td>GOOD</td>
        <td>90-95%</td>
        <td>Acceptable (+5-10% = $4k-12k)</td>
        <td>Normal operations</td>
        <td>Most firms target this range</td>
      </tr>
      <tr>
        <td>ACCEPTABLE</td>
        <td>85-90%</td>
        <td>Moderate (+10-15% = $8k-18k)</td>
        <td>Learning phase (Q1-Q3) or volatile markets</td>
        <td>Improve methods, build safety stock</td>
      </tr>
      <tr>
        <td>POOR</td>
        <td>&lt;85%</td>
        <td>Severe (+15%+ = $12k-80k)</td>
        <td>Operational chaos (emergencies, stockouts)</td>
        <td>Immediate attention required</td>
      </tr>
    </table>
    <p>Practical Exercise: Forecast Optimization</p>
    <p>Q3 Results:</p>
    <p>• SHA: Forecast 45k, Actual 50k (90% accuracy)
      • ST: Forecast 30k, Actual 40k (75% accuracy)</p>
    <p>Q4 Planning:</p>
    <p>• SHA trend: +10% growth (Q2: 45k, Q3: 50k)
      • ST trend: +20% growth (Q2: 33k, Q3: 40k)</p>
    <p>Calculate:</p>
    <ul>
      <li>Q3 overhead penalty (Retail products)</li>
      <li>Q4 forecast using trend method</li>
      <li>Q4 forecast using conservative method (90% of trend)</li>
      <li>Which approach minimizes overhead risk?</li>
    </ul>
    <p>Solution:</p>
    <p>a) Q3 Overhead Penalty:</p>
    <p>SHA (90% accuracy = 10% error):
      • Base overhead: $80k (assuming Retail)
      • Adjusted: $80k × 1.10 = $88k
      • Penalty: $8k</p>
    <p>ST (75% accuracy = 25% error):
      • Base overhead: $80k
      • Adjusted: $80k × 1.25 = $100k
      • Penalty: $20k</p>
    <p>Total Q3 penalty: $28k</p>
    <p>b) Q4 Forecast Using Trend Method:</p>
    <p>• SHA: 50k × 1.10 = 55k
      • ST: 40k × 1.20 = 48k</p>
    <p>c) Q4 Forecast Using Conservative Method (90% of trend):</p>
    <p>• SHA: 55k × 0.90 = 49.5k ≈ 50k
      • ST: 48k × 0.90 = 43.2k ≈ 43k</p>
    <p>d) Which Approach Minimizes Overhead Risk?</p>
    <p>Analysis:</p>
    <p>If actual Q4 demand = 55k SHA, 48k ST:</p>
    <ul>
      <li>Trend method: 100% accuracy (no penalty)</li>
      <li>Conservative method: 91% SHA, 90% ST accuracy (small penalty ~$8-10k)</li>
    </ul>
    <p>If actual Q4 demand lower than trend (e.g., 52k SHA, 45k ST):</p>
    <ul>
      <li>Trend method: 95% SHA, 94% ST accuracy (penalty ~$8k)</li>
      <li>Conservative method: 96% SHA, 96% ST accuracy (penalty ~$6k)</li>
    </ul>
    <p>RECOMMENDATION: Conservative method (90% of trend) provides better risk-adjusted accuracy. The small
      underforecast (5-10%) can be covered by safety stock, while avoiding the larger overhead penalties from
      overforecasting.</p>
    <h2 id="ch9-chapter-summary-key-takeaways">Chapter Summary: Key Takeaways</h2>
    <ul>
      <li>Forecast Accuracy Directly Affects Overhead
        Every 1% error adds 1% to administrative overhead. Poor forecasting (70% accuracy) costs $500k+ over 8
        quarters.</li>
      <li>Target 90-95% Accuracy
        Excellent range for most firms. Acceptable overhead penalty ($4-12k) and operational stability.</li>
      <li>Start Conservative In Q1
        No historical data means high uncertainty. Forecast 80-90% of optimistic estimates, build safety stock.
      </li>
      <li>Use Simple Methods (Naive, Moving Average, Trend)
        Complex models don't help in short simulations. Pattern recognition beats sophisticated math.</li>
      <li>Forecast For Accuracy, Not Ambition
        "I want to sell 100k" is not a forecast. Forecast what you will actually sell, then build slightly more.
      </li>
      <li>Smooth Large Swings
        Don't forecast 50k then 80k. Motherboard lead times can not handle 60% jumps. Smooth to 50k → 60k → 70k.
      </li>
      <li>Include Replacement Demand
        Defect Rate generates replacement sales. 2% × 50k = 1k additional units. Don't forget this.</li>
      <li>Reconcile Top-Down And Bottom-Up
        Market share estimates vs. sum of regional forecasts. Use both perspectives, adjust if mismatch.</li>
    </ul>
    <p>Before finalizing forecasts each quarter:</p>
    <ul>
      <li>Review last quarter's forecast accuracy (learn from errors)</li>
      <li>Identify demand patterns (growth, stable, decline)</li>
      <li>Calculate conservative estimate (90% of trend if uncertain)</li>
      <li>Factor in replacement demand (Defect Rate × Sales)</li>
      <li>Reconcile with regional bottom-up forecasts</li>
      <li>Remember: Accuracy &gt; Ambition</li>
    </ul>
    <p>Forecasting is less glamorous than pricing or marketing, but it's foundational. Get forecasting right, and
      the rest follows. Get it wrong, and every decision downstream suffers.</p>
  </section>
@endsection
