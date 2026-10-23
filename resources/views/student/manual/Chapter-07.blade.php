@extends('student.manual.layout')
@section('title', 'Chapter 7: Service Decision')
@section('template')
  <section id="chapter-7-service-decisions">
    <h1>CHAPTER 7: SERVICE DECISIONS</h1>
    <div class="learning-objectives">
      <table class="doc-table">
        <tr>
          <td>
            <h2>Learning Objectives:</h2>
            <p>By the end of this chapter, you will be able to:</p>
            <ul>
              <li>Understand how service decisions are modeled in the simulation</li>
              <li>Evaluate service levels as fixed operating cost choices</li>
              <li>Analyze the impact of service spending on operating income and net profit</li>
              <li>Align service decisions with cost control or premium positioning strategies</li>
            </ul>
          </td>
        </tr>
      </table>
    </div>

    <h2 id="ch7-service-decision-overview">Service Decision Overview</h2>
    <p>Customer service in FLEXEE is outsourced to third-party providers in each region. You select the service
      level, which determines:</p>
    <ul>
      <li>Cost per service call</li>
      <li>Service level guarantee commitment</li>
      <li>Impact on operating costs and margins</li>
    </ul>
    <p>Service is straightforward compared to other decisions, but it's strategically important because service
      costs directly impact your controllable costs and operating margin. Higher service levels increase costs but
      may support premium pricing strategies and customer retention.</p>
    <table class="doc-table">
      <tr>
        <th>Real-World Context: Service As Differentiator</th>
        <th>Real-World Context: Service As Differentiator</th>
      </tr>
      <tr>
        <td>Zappos, the online shoe retailer, built its business model around premium customer service, offering
          24/7 support and free returns despite annual costs exceeding $100 million. This service investment
          generated remarkable customer loyalty: 75% of purchases came from repeat customers, creating a customer
          lifetime value that Amazon recognized when acquiring Zappos for $1.2 billion. The acquisition price
          reflected how service quality directly drove company valuation.</td>
        <td>Apple's Genius Bar represents another strategic service investment, providing free in-store technical
          support across 500+ retail locations at annual costs exceeding $1 billion in staffing alone. This
          substantial expense generates customer satisfaction scores consistently above 85, compared to 70 for
          competitors like Dell and HP. The elevated satisfaction justifies Apple's $200+ price premium over
          comparable hardware, demonstrating how service quality enables premium pricing strategies.</td>
      </tr>
      <tr>
        <td>In FLEXEE, your service level decisions should similarly align with your overall competitive
          positioning.</td>
        <td>In FLEXEE, your service level decisions should similarly align with your overall competitive
          positioning.</td>
      </tr>
    </table>
    <h2 id="ch7-service-level-options">Service Level Options</h2>
    <p>For each region (R1, R2, R3), you select one service level from five options ranging from no service to
      premium support. Service costs scale with regional operating expenses, making R3 the most expensive region
      due to higher local labor and logistics costs.</p>
    <p>Service Levels &amp; Costs:</p>
    <table class="doc-table">
      <tr>
        <th>Level</th>
        <th>Description</th>
        <th>R1 Cost/Call</th>
        <th>R2 Cost/Call</th>
        <th>R3 Cost/Call</th>
        <th>SQ Guarantee</th>
      </tr>
      <tr>
        <td>0</td>
        <td>No Service</td>
        <td>N/A</td>
        <td>N/A</td>
        <td>N/A</td>
        <td>0%</td>
      </tr>
      <tr>
        <td>1</td>
        <td>Minimum</td>
        <td>$7.50</td>
        <td>$9.00</td>
        <td>$10.00</td>
        <td>90%</td>
      </tr>
      <tr>
        <td>2</td>
        <td>Standard</td>
        <td>$12.50</td>
        <td>$15.00</td>
        <td>$16.50</td>
        <td>95%</td>
      </tr>
      <tr>
        <td>3</td>
        <td>Enhanced</td>
        <td>$20.00</td>
        <td>$22.50</td>
        <td>$26.00</td>
        <td>98%</td>
      </tr>
      <tr>
        <td>4</td>
        <td>Premium</td>
        <td>$30.00</td>
        <td>$33.75</td>
        <td>$40.00</td>
        <td>100%</td>
      </tr>
    </table>
    <p>Your service level choice should align with your overall competitive strategy---see Chapter 8 for how
      service levels support cost leadership versus differentiation positioning.</p>
    <p>Key Observations:</p>
    <p>1. No Service (Level 0) Is Risky</p>
    <ul>
      <li>SQ = 0% → CSI collapses if any issues arise</li>
      <li>Only viable if Product Quality and Availability Quality are perfect</li>
      <li>Rarely recommended</li>
    </ul>
    <p>2. Costs Scale With Region</p>
    <ul>
      <li>R3 is most expensive (10-33% more than R1)</li>
      <li>Reflects local labor costs, logistics</li>
    </ul>
    <p>3. SQ Guarantee Increases With Level</p>
    <ul>
      <li>Minimum: 90% (adequate for most)</li>
      <li>Standard: 95% (solid)</li>
      <li>Enhanced: 98% (premium)</li>
      <li>Premium: 100% (perfect, very expensive)</li>
    </ul>
    <h2 id="ch7-chapter-summary-key-takeaways">Chapter Summary: Key Takeaways</h2>
  </section>
@endsection
