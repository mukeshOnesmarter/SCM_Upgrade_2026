@extends('student.manual.layout')
@section('title', 'Simulation Manual')
@section('template')
  <section id="preface-how-to-use-this-manual">
    <h1>PREFACE: HOW TO USE THIS MANUAL</h1>
    <p>This manual is your companion for navigating the FLEXEE Supply Chain Management Simulation. It is
      deliberately designed to be more than a rulebook—it is a teaching resource, a decision support
      tool, and a bridge between supply chain theory and practice.</p>
    <h2 id="preface-why-this-manual-exists">Why This Manual Exists</h2>
    <p>Business school case studies teach you to analyze decisions after they have been made. FLEXEE
      does something different: it puts you in the position of making those decisions yourself, in
      real time, with incomplete information and competing pressures. You will experience what it
      feels like when:</p>
    <ul>
      <li>A cheaper supplier seems attractive until defect rates spike</li>
      <li>Cutting production conserves cash but destroys customer satisfaction</li>
      <li>Opening a distribution center requires $300,000 today for benefits that appear next quarter
      </li>
      <li>A 10% forecast error cascades into emergency procurement, inflated costs, and eroding
        margins</li>
    </ul>
    <p>These are not hypothetical scenarios. They are the daily reality for supply chain managers at
      Amazon, Toyota, Apple, and thousands of smaller firms. This manual will help you navigate these
      trade-offs with intention, not panic.</p>
    <h2 id="preface-how-to-use-this-manual1">How to Use This Manual</h2>
    <p>DO NOT try to memorize everything at once. Instead, think of this manual as having three distinct
      uses:</p>
    <p>1. Before You Start: Foundation Building
      Read the Preface, Chapter 1 (Introduction), and Chapter 2 (Products) to understand the
      simulation's structure and your role.</p>
    <p>2. During Decision-Making: Just-in-Time Reference
      When placing procurement orders, open Chapter 3 (Procurement).
      When planning production, open Chapter 4 (Manufacturing).
      When analyzing results, open Chapter 11 (Financial Reports).</p>
    <p>Each chapter is designed to stand alone so you can jump to what you need without reading
      cover-to-cover.</p>
    <p>3. After Each Quarter: Reflective Analysis
      Use Chapter 11 (Financial Reports) and Chapter 12 (Performance Evaluation) to diagnose what
      happened and why.</p>
    <h2 id="preface-learning-philosophy-the-three-levels-of-mastery">Learning Philosophy: The Three
      Levels of Mastery</h2>
    <p>FLEXEE is designed around three levels of learning:</p>
    <p>Level 1: Functional Competence (Quarters 1-2)
      "What buttons do I push? What do these numbers mean?"</p>
    <p>At this level, you are learning the mechanics: How to place procurement orders, how to read the
      Income Statement, what the dashboard metrics measure.</p>
    <p>Level 2: Systems Thinking (Quarters 3-5)
      "How do my decisions in procurement affect production, which affects distribution, which affects
      cash flow?"</p>
    <p>At this level, you begin seeing the interconnections: A procurement decision today affects
      inventory next quarter, which affects cash flow, which affects your ability to open distribution
      centers.</p>
    <p>Level 3: Strategic Judgment (Quarters 6+)
      "What trade-offs am I willing to make? How do I balance short-term survival with long-term
      competitiveness?"</p>
    <p>At this level, you are making informed strategic choices based on clear understanding of
      consequences and alternatives.</p>
  </section>
@endsection
