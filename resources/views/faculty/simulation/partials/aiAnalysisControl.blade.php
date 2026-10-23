<div class="aiAnalysisControl">
  <div class="sectionTitle">
    <h5>AI Analysis Report</h5>
    <p> This setting is managed by the system administrator and determines whether AI-generated quarterly analysis
      reports are created.</p>
  </div>
  <div class="inviteDataForm">
    <div class="item">
      <label>Current Status</label>
      @if ($simulation->is_analysis_enabled)
        <span class="status-badge enabled"> <i class="fa fa-check-circle"></i> Enabled</span>
        <small class="help_text">AI insight reports will be generated during initialization and quarter closing.</small>
      @else
        <span class="status-badge disabled"><i class="fa fa-times-circle"></i> Disabled</span>
        <small class="help_text">AI insight reports are currently disabled by the administrator.</small>
      @endif
    </div>
  </div>
</div>
