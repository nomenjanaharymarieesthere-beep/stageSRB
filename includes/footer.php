    </div>
  </div>
</div>
<script>
document.addEventListener('click', function(e){
  const panel = document.getElementById('notifPanel');
  const btn = document.querySelector('.notif-btn');
  if (panel && panel.classList.contains('show') && !panel.contains(e.target) && e.target !== btn) {
    panel.classList.remove('show');
  }
});
function toggleNotif(btn){
  var url = '<?= root_url('notif_lu.php') ?>';
  const panel = document.getElementById('notifPanel');
  const willOpen = !panel.classList.contains('show');
  panel.classList.toggle('show');
  if (willOpen){
    var dot = document.getElementById('notifDot');
    if (dot){ dot.style.display = 'none'; }
    if (navigator.sendBeacon){
      navigator.sendBeacon(url);
    } else {
      var x = new XMLHttpRequest();
      x.open('GET', url);
      x.send();
    }
  }
}
</script>
</body>
</html>
