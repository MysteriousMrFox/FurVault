function buildDownloadList(CSRF, search, name){
    fetch("/actions/e621/ajax-build_list", {
        method: "POST",
        credentials: "include",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "CSRF=" + CSRF + "&name=" + encodeURIComponent(name) + "&search=" + encodeURIComponent(search)
    }).then(response => {
        switch(response.status){
            case 403:
                document.querySelectorAll("[data-build-button]").forEach(element => element.classList.remove("disabled"));
                alert("CSRF Check Failure. Refresh and try again!");
                break;
            
            case 400:
                document.querySelectorAll("[data-build-button]").forEach(element => element.classList.remove("disabled"));
                alert("Enter a search term and try again");
                break;

            case 409:
                document.querySelectorAll("[data-build-button]").forEach(element => element.classList.remove("disabled"));
                alert("Another build is currently in progress. Go to the Build status page to view it");
                break;

            case 200:
                window.location.href = "/e621/build/status";
                break;

            default:
                document.querySelectorAll("[data-build-button]").forEach(element => element.classList.remove("disabled"));
                alert("Something went wrong starting the build (" + response.status + " [" + response.statusText + "])");
                break;
        }
    });
}

function getListBuildStatus(CSRF){
    return fetch("/actions/e621/ajax-build_list_status?CSRF=" + CSRF).then(response => {
        switch(response.status){
            case 409:
                return null;
            
            case 200:
                return response.json();

            default:
                alert("Something went wrong getting the build status (" + response.status + " [" + response.statusText + "])");
                return null;
        }
    });
}

function cancelListBuild(CSRF){
    document.querySelector("#cancel-button").classList.add("disabled");

    return fetch("/actions/e621/ajax-build_list_cancel?CSRF=" + CSRF).then(response => {
        switch(response.status){
            case 409:
                return null;
            
            case 200:
                return response.json();

            default:
                alert("Something went wrong cancelling the build (" + response.status + " [" + response.statusText + "])");
                return null;
        }
    });
}

function getDownloadStatus(CSRF){
    return fetch("/actions/e621/ajax-download_status?CSRF=" + CSRF).then(response => {
        switch(response.status){
            case 409:
                return null;
            
            case 200:
                return response.json();

            default:
                alert("Something went wrong getting the download status (" + response.status + " [" + response.statusText + "])");
                return null;
        }
    });
}

function cancelDownload(CSRF){
    document.querySelector("#cancel-button").classList.add("disabled");

    return fetch("/actions/e621/ajax-download_cancel?CSRF=" + CSRF).then(response => {
        switch(response.status){
            case 409:
                return null;
            
            case 200:
                return response.json();

            default:
                alert("Something went wrong cancelling the download (" + response.status + " [" + response.statusText + "])");
                return null;
        }
    });
}

function pauseDownload(CSRF){
    document.querySelector("#pause-button").classList.add("disabled");

    return fetch("/actions/e621/ajax-download_pause?CSRF=" + CSRF).then(response => {
        switch(response.status){
            case 409:
                return null;
            
            case 200:
                return response.json();

            default:
                alert("Something went wrong pausing the download (" + response.status + " [" + response.statusText + "])");
                return null;
        }
    });
}

function unpauseDownload(CSRF){
    document.querySelector("#resume-button").classList.add("disabled");

    return fetch("/actions/e621/ajax-download_unpause?CSRF=" + CSRF).then(response => {
        switch(response.status){
            case 200:
                window.location = "/e621/download/status";
                break;

            default:
                alert("Something went wrong pausing the download (" + response.status + " [" + response.statusText + "])");
                return null;
        }
    });
}