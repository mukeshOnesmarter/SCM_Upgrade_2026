@extends('student.manual.layout')
@section('title', 'Chapter 10: Enterprise Integration')
@section('template')
  <section id="chapter-10-enterprise-integration">
    <h1>CHAPTER 10: ENTERPRISE INTEGRATION</h1>
    <div class="learning-objectives">
      <table class="doc-table">
        <tr>
          <td>
            <h2>Learning Objectives:</h2>
            <p>By the end of this chapter, you will be able to:</p>
            <ul>
              <li>Evaluate IT synchronization investments with suppliers and carriers</li>
              <li>Calculate ROI for IT synchronization based on volume and reliability improvements</li>
              <li>Understand how IT synchronization reduces operational risks</li>
              <li>Align IT investments with strategic partnerships</li>
            </ul>
          </td>
        </tr>
      </table>
    </div>

    <h2 id="ch10-enterprise-integration-as-operational-excellence">Enterprise Integration As Operational
      Excellence</h2>
    <p>Enterprise Integration involves IT synchronization with supply chain partners—suppliers and carriers—to
      improve reliability and reduce operational failures through automated information sharing and coordinated
      planning. Supplier synchronization delivers two primary benefits: delivery reliability increases by 2-6
      percentage points, and component failure rates decrease by 0.1-0.5 percentage points. Carrier
      synchronization focuses on a single metric, improving delivery reliability by 2-10 percentage points
      depending on the carrier's baseline performance.</p>
    <p>These improvements require modest investment. Setup costs range from $5,000 to $9,000 as a one-time expense
      covering systems integration and initial coordination. Ongoing quarterly costs run $3,000 to $9,000 to
      maintain the synchronized information systems and relationship management. The investment decision depends
      primarily on volume: high-volume partnerships justify the costs through accumulated reliability gains and
      avoided failures, while low-volume relationships may not generate sufficient benefits to cover the fixed
      quarterly expenses.</p>
    <h2 id="ch10-part-1-supplier-it-synchronization">Part 1: Supplier IT Synchronization</h2>
    <p>IT synchronization with suppliers improves both delivery reliability and reduces component failure rates
      through automated information sharing and coordination. Seven suppliers offer IT sync capabilities with
      varying setup costs, ongoing quarterly fees, and performance improvements. The investment decision depends
      on order volumes and the criticality of components.</p>
    <h3 id="ch10-supplier-sync-options">Supplier Sync Options:</h3>
    <table class="doc-table">
      <tr>
        <th>Supplier</th>
        <th>Setup</th>
        <th>Quarterly</th>
        <th>Reliability Gain</th>
        <th>Failure Reduction</th>
      </tr>
      <tr>
        <td>A</td>
        <td>$9k</td>
        <td>$7k</td>
        <td>+5%</td>
        <td>-0.2%</td>
      </tr>
      <tr>
        <td>B</td>
        <td>$8k</td>
        <td>$7k</td>
        <td>+4%</td>
        <td>-0.1%</td>
      </tr>
      <tr>
        <td>C</td>
        <td>$9k</td>
        <td>$9k</td>
        <td>+6%</td>
        <td>-0.4%</td>
      </tr>
      <tr>
        <td>D</td>
        <td>$9k</td>
        <td>$8k</td>
        <td>+3%</td>
        <td>-0.5%</td>
      </tr>
      <tr>
        <td>E</td>
        <td>$6k</td>
        <td>$6k</td>
        <td>+4%</td>
        <td>-0.4%</td>
      </tr>
      <tr>
        <td>F</td>
        <td>$7k</td>
        <td>$5k</td>
        <td>+5%</td>
        <td>-0.3%</td>
      </tr>
      <tr>
        <td>G</td>
        <td>$7k</td>
        <td>$5k</td>
        <td>+6%</td>
        <td>-0.3%</td>
      </tr>
    </table>
    <p>ROI Calculation Example: Supplier E (Motherboards), 80k Units/Quarter</p>
    <p>Without IT sync:</p>
    <ul>
      <li>Reliability: 75%</li>
      <li>Failure rate: 2.1%</li>
      <li>Expected usable: 80k × 75% × 97.9% = 58,740 units</li>
    </ul>
    <p>With IT sync:</p>
    <ul>
      <li>Reliability: 79% (+4%)</li>
      <li>Failure rate: 1.7% (-0.4%)</li>
      <li>Expected usable: 80k × 79% × 98.3% = 62,123 units</li>
      <li>Improvement: 3,383 more usable units</li>
    </ul>
    <p>VALUE OF IMPROVEMENT:</p>
    <ul>
      <li>Fewer emergencies: 3,383 × $8 (emergency MB premium) = $27,064</li>
      <li>Lower defects: 320 fewer failures × $150 (cost per failure) = $48,000</li>
      <li>Total benefit: ~$75k/quarter</li>
    </ul>
    <p>IT sync cost: $6k setup + $6k/quarter = $12k Q1, $6k ongoing</p>
    <p>ROI: $75k benefit ÷ $6k cost = 12.5× return (excellent!)</p>
    <h2 id="ch10-part-2-carrier-it-synchronization">Part 2: Carrier IT Synchronization</h2>
    <p>Carrier IT synchronization improves delivery reliability through real-time tracking, automated exception
      handling, and coordinated scheduling. Six carriers offer IT sync with varying costs and reliability
      improvements, with effectiveness particularly high for carriers with initially poor surface reliability.</p>
    <h3 id="ch10-carrier-sync-options">Carrier Sync Options:</h3>
    <table class="doc-table">
      <tr>
        <th>Carrier</th>
        <th>Setup</th>
        <th>Quarterly</th>
        <th>Reliability Gain</th>
      </tr>
      <tr>
        <td>I</td>
        <td>$9k</td>
        <td>$7k</td>
        <td>+5%</td>
      </tr>
      <tr>
        <td>J</td>
        <td>$8k</td>
        <td>$7k</td>
        <td>+10%</td>
      </tr>
      <tr>
        <td>K</td>
        <td>$9k</td>
        <td>$9k</td>
        <td>+6%</td>
      </tr>
      <tr>
        <td>L</td>
        <td>$9k</td>
        <td>$8k</td>
        <td>+3%</td>
      </tr>
      <tr>
        <td>M</td>
        <td>$6k</td>
        <td>$6k</td>
        <td>+4%</td>
      </tr>
      <tr>
        <td>N</td>
        <td>$5k</td>
        <td>$3k</td>
        <td>+2%</td>
      </tr>
    </table>
    <p>The largest reliability gains come from carriers with initially low performance—Carrier J improves 10
      percentage points from its 40% baseline, while premium Carrier N gains only 2 points from its already-high
      82% baseline. This pattern suggests IT sync delivers greatest value when applied to moderate-cost,
      moderate-reliability carriers rather than premium options.</p>
    <h2 id="ch10-chapter-summary-key-takeaways">Chapter Summary: Key Takeaways</h2>
    <ul>
      <li>IT Sync Improves Reliability And Reduces Failures
        Suppliers: +2-6% reliability, -0.1-0.5% failures. Carriers: +2-10% reliability. These efficiency gains
        reduce administrative overhead and improve your Operating Margin - see Chapter 11 for how IT investments
        affect your Income Statement and ROA.</li>
      <li>Costs Are Moderate: $5k-9k Setup, $3k-9k/Quarter
        One-time investment plus ongoing quarterly maintenance.</li>
      <li>ROI Depends On Volume
        High-volume partners (&gt;50k units) justify IT sync easily. Low-volume (</li>
      <li>Prioritize Critical Components
        Motherboards (1Q lead time) benefit most. Plastics (instant) benefit least.</li>
      <li>Synergy With Exclusive Carrier Contracts
        If committed to one carrier (20% rebate), IT sync mitigates single-partner risk.</li>
      <li>Typical Payback: 1-3 Quarters
        High-volume suppliers: immediate. Moderate carriers: 2-3 quarters.</li>
      <li>Implement In Q3-Q4, Not Q1
        Wait until partnerships are confirmed and volume is established.</li>
      <li>IT Sync Is "Operational Insurance"
        Pays for itself through avoided emergencies and reduced defects.</li>
    </ul>
  </section>
@endsection
