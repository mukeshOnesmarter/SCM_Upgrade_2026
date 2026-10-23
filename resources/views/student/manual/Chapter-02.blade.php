@extends('student.manual.layout')
@section('title', 'Chapter 2: Product Description')
@section('template')
  <section id="chapter-2-products-you-manage">
    <h1>CHAPTER 2: PRODUCTS YOU MANAGE</h1>
    <div class="learning-objectives">
      <table class="doc-table">
        <tr>
          <td>
            <h2>Learning Objectives:</h2>
            <p>By the end of this chapter, you will be able to:</p>
            <ul>
              <li>Describe the two products, SHA and ST, and their component structures</li>
              <li>Explain how shared components create economies of scale and shared risks</li>
              <li>Calculate total product costs, including materials, assembly, warranty, and packaging</li>
              <li>Understand how warranty and packaging choices affect both costs and customer perception</li>
              <li>Apply the Bill of Materials (BOM) concept to supply chain planning</li>
            </ul>
          </td>
        </tr>
      </table>
    </div>

    <h2 id="ch2-your-product-portfolio">Your Product Portfolio</h2>
    <p>You manage two products in the consumer electronics category. The Smart Home Assistant (SHA) is a
      voice-activated device for home automation and information, comparable to products like Amazon Echo or
      Google Home. The Smart Thermostat (ST) is a programmable climate control device with learning capabilities,
      similar to the Nest Thermostat or Ecobee.</p>
    <h2 id="ch2-smart-home-assistant-sha">Smart Home Assistant (SHA)</h2>
    <p>Each SHA unit requires a precise bill of materials combining raw materials, sub-assemblies, and optional
      features. The raw materials include five kilograms each of plastics and casings. Required sub-assemblies are
      one motherboard and one audio module. You also select warranty coverage (zero to four quarters) and choose
      from three packaging options: standard, premium, or eco-friendly.</p>
    <p>Warranty coverage and packaging options are predefined at the product level and are automatically included
      in the product cost.
      Students do not select warranty duration or packaging type during decision-making.</p>
    <p>Component Details</p>
    <p>Plastics (5 kg per unit) represent the first component in the SHA bill of materials, used at five kilograms
      per unit. The base price is $4 per kilogram, with volume discounts available at three tiers. Orders
      exceeding 250,000 kg receive an 8% discount, reducing the cost to $3.68 per kilogram. At 500,000 kg, the
      discount increases to 14%, lowering costs to $3.44 per kilogram. The maximum discount tier activates at
      1,000,000 kilograms, providing a 19% reduction to $3.24 per kilogram. Emergency procurement carries a $1 per
      kilogram surcharge, raising the emergency cost to $5.00 per kilogram. At base pricing, plastics cost $20 per
      SHA unit (5 kg × $4).</p>
    <p>Casings (5 kg per unit) follow the same volume discount structure as plastics. The base price is $5.00 per
      kilogram, with identical discount tiers: 8% off at 250,000 kg ($4.60/kg), 14% off at 500,000 kg ($4.30/kg),
      and 19% off at 1,000,000 kg ($4.05/kg). Emergency casings also carry a $1 per kilogram surcharge. At base
      pricing, casings cost $25 per SHA unit (5 kg × $5.00).</p>
    <p>Motherboards (1 per unit) are critical components with a one-quarter lead time, meaning you must order in
      Q1 for Q2 delivery. This lead time makes motherboards the most planned-intensive component in your supply
      chain. Motherboards are available from four suppliers: D, E, F, and G, with prices ranging from $19 to $29
      per unit depending on supplier quality and reliability. The average cost is approximately $22 per unit.</p>
    <p>Audio Modules (1 per unit) provide the voice interaction capability unique to the SHA product. Unlike
      motherboards, audio modules offer immediate delivery, arriving in the same quarter they are ordered. Four
      suppliers (A, B, C, and D) offer audio modules with prices ranging from $11 to $16 per unit, depending on
      supplier quality and reliability. Supplier D provides the highest quality and reliability, while lower-cost
      options trade off higher failure rates.</p>
    <p>Warranty Coverage is optional and ranges from zero to four quarters of protection. The warranty cost
      formula is $8 + 3Q², where Q represents the number of quarters. A one-quarter warranty costs $11 ($8 +
      3×1²). Two quarters cost $20 ($8 + 3×4). Three quarters cost $35 ($8 + 3×9). The maximum four-quarter
      warranty costs $56 ($8 + 3×16). Warranty decisions affect both product cost and customer perception of
      quality. Warranty impacts Product Quality (PQ), which is one-third of your Customer Satisfaction Index—see
      Chapter 12 for how warranty choices affect CSI and overall performance.</p>
    <p>Packaging Choices include three options with different cost and positioning implications. Standard
      packaging costs $10 and provides basic cardboard box protection. Premium packaging costs $14 and includes
      reinforced materials and branded design elements. Eco-friendly packaging costs $28 and uses recycled
      materials with sustainability messaging, appealing to environmentally conscious customers while
      significantly increasing product cost.</p>
    <p>SHA Total Unit Cost</p>
    <p>The total SHA unit cost varies significantly based on your component and feature selections. A base
      configuration with no warranty and standard packaging totals $92: plastics ($20), casings ($25), motherboard
      ($22), audio module ($15), and standard packaging ($10). At the opposite extreme, a premium configuration
      with four-quarter warranty and eco-friendly packaging costs $166: the same component costs plus $56 for
      warranty and $28 for eco packaging. Your configuration choices therefore create a $74 cost range per unit,
      directly impacting pricing strategy and target market positioning.</p>
    <h2 id="ch2-smart-thermostat-st">Smart Thermostat (ST)</h2>
    <p>Each ST unit follows a similar bill of materials structure. Raw materials requirements are identical to the
      SHA: five kilograms each of plastics and casings. The ST also requires one motherboard per unit. The key
      difference is the sub-assembly: instead of an audio module, the ST uses a control interface for climate
      control functionality. Like the SHA, you select warranty coverage and packaging options.</p>
    <p>Control Interface</p>
    <p>The Control Interface distinguishes the ST from the SHA in both functionality and supply chain
      characteristics. Like audio modules, control interfaces offer immediate delivery within the same quarter you
      order them. Five suppliers (B, C, D, E, and F) provide control interfaces with prices ranging from $13 to
      $24 per unit, averaging approximately $16 per unit. This $1 higher average cost compared to audio modules
      accounts for the ST is slightly higher base unit cost.</p>
    <p>ST Total Unit Cost</p>
    <p>The base ST configuration costs $93 compared to $92 for the SHA due to the slightly higher Control
      Interface cost. Like the SHA, warranty and packaging selections create the same $74 cost range, allowing ST
      configurations from $93 to $167.</p>
    <h2 id="ch2-chapter-summary-key-takeaways">Chapter Summary: Key Takeaways</h2>
    <ul>
      <li>You manage two products in the consumer electronics category: The Smart Home Assistant (SHA) is a
        voice-activated device for home automation, and the Smart Thermostat (ST) is a programmable climate
        control device. Both share common components but serve different functions.</li>
      <li>SHA and ST share three core components: Both products require 5 kg of plastics ($20/unit), 5 kg of
        casings ($25/unit), and one motherboard ($19-29 depending on supplier, averaging $22). This component
        sharing creates economies of scale and procurement efficiencies.</li>
      <li>Product-specific sub-assemblies differentiate the two products: SHA requires an Audio Module ($12-22,
        averaging $15) for voice interaction, while ST uses a Control Interface ($13-24, averaging $16) for
        climate control. Both components offer immediate same-quarter delivery.</li>
      <li>Motherboards have the longest lead time: With one-quarter lead time, motherboards require advance
        planning and forecasting. You must order in Q1 for Q2 delivery, making this the most planning-intensive
        component in your supply chain.</li>
      <li>Optional features create significant cost variation: Warranty coverage ranges from $0 (no warranty) to
        $56 (four quarters, using formula $8 + 3Q²). Packaging choices span $10 (standard), $14 (premium), or $28
        (eco-friendly). These selections create a $74 cost range per unit.</li>
      <li>Base product costs are nearly identical: SHA base configuration costs $92 (no warranty, standard
        packaging) while ST costs $93 due to the slightly more expensive Control Interface. Premium configurations
        reach $166-167 with maximum warranty and eco-friendly packaging.</li>
      <li>Component choices directly impact strategy: Lower-cost configurations ($92-93) enable cost leadership
        positioning with aggressive pricing and high volume. Premium configurations ($140-167) support
        differentiation strategies with superior warranty coverage and sustainable packaging that justify premium
        pricing.</li>
    </ul>
  </section>
@endsection
