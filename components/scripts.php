<script>
    function keepalive(){
        fetch("/actions/ajax-keepalive").then(response => {
            switch(response.status){
                case 403:
                    window.location.reload();
                    break;
                
                case 200:
                    //Ignore
                    break;

                default:
                    console.log("Keepalive invalid response code " + response.status, response);
                    break;
            }
        });
    }

    setInterval(keepalive, 30000);
</script>
<script>
    document.querySelectorAll("[data-timecalc-date]").forEach(element => {
        element.innerText = new Date(element.getAttribute("data-timecalc-date") * 1000).toLocaleDateString();
    });

    document.querySelectorAll("[data-timecalc-time]").forEach(element => {
        element.innerText = new Date(element.getAttribute("data-timecalc-time") * 1000).toLocaleTimeString();
    });
</script>