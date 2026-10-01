/**
 * charts.js — ApexCharts integration (CDN, optional)
 */
function initCharts() {
  if (typeof window.ApexCharts === 'undefined') return;

  const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

  // Department bar chart
  const barEl = document.getElementById('deptChart');
  if (barEl && !barEl._chart) {
    barEl._chart = new window.ApexCharts(barEl, {
      chart:  { type: 'bar', height: 240, toolbar: { show: false }, background: 'transparent' },
      theme:  { mode: isDark ? 'dark' : 'light' },
      colors: ['#4F6EF7'],
      plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
      dataLabels: { enabled: false },
      series: [{ name: 'Employees', data: [68, 42, 35, 28, 22, 18] }],
      xaxis: {
        categories: ['Engineering', 'Product', 'Sales', 'Marketing', 'HR', 'Finance'],
        axisBorder: { show: false },
        axisTicks:  { show: false },
      },
      grid: { borderColor: isDark ? '#1A2035' : '#E4E6F0', strokeDashArray: 4 },
    });
    barEl._chart.render();
  }

  // Attendance donut chart
  const donutEl = document.getElementById('attendanceDonut');
  if (donutEl && !donutEl._chart) {
    donutEl._chart = new window.ApexCharts(donutEl, {
      chart:  { type: 'donut', height: 220, background: 'transparent' },
      colors: ['#10B981', '#F59E0B', '#EF4444'],
      series: [261, 18, 5],
      labels: ['Present', 'Leave', 'Absent'],
      dataLabels: { enabled: false },
      legend: { position: 'bottom', fontSize: '12px' },
      plotOptions: { pie: { donut: { size: '70%' } } },
    });
    donutEl._chart.render();
  }
}
