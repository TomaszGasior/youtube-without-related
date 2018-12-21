function onYouTubeIframeAPIReady()
{
    function YtpwrelWrapper(wrapperNode)
    {
        const OVERLAY_CSS_CLASS = 'show-overlay';

        this.wrapperNode = wrapperNode;
        this.ytPlayer = new YT.Player(wrapperNode.children[0]);

        this.hideOverlayWithDelay = false;
        this.playerLastTime = 0;

        this.onPlayerStateChange = function()
        {
            switch(this.ytPlayer.getPlayerState()) {
                case YT.PlayerState.PAUSED:
                    // There is no other way to find out whether PAUSED state is caused
                    // by direct user action (click on pause button) or by seeking.
                    // Without this hack overlay is shown when user seeks.
                    if (Math.abs(this.playerLastTime - this.ytPlayer.getCurrentTime()) > 5) {
                        setTimeout(function(){
                            if (this.ytPlayer.getPlayerState() == YT.PlayerState.PAUSED) {
                                this.wrapperNode.classList.add(OVERLAY_CSS_CLASS);
                            }
                        }.bind(this), 100);
                        break;
                    }
                    // Don't break otherwise.

                case YT.PlayerState.ENDED:
                    this.wrapperNode.classList.add(OVERLAY_CSS_CLASS);
                    break;

                case YT.PlayerState.PLAYING:
                    // When playing is restarted after click on overlay, it's needed to
                    // hide overlay with delay. YouTube player needs some time to hide
                    // related movies box (end hiding animation).
                    if (this.hideOverlayWithDelay) {
                        setTimeout(function(){
                            this.wrapperNode.classList.remove(OVERLAY_CSS_CLASS);
                            this.hideOverlayWithDelay = false;
                        }.bind(this), 200);
                    }
                    else {
                        this.wrapperNode.classList.remove(OVERLAY_CSS_CLASS);
                    }
                    break;
            }

        }
        this.onOverlayClick = function()
        {
            this.hideOverlayWithDelay = true;
            this.ytPlayer.playVideo();
        }
        this.updatePlayerLastTime = function()
        {
            this.playerLastTime = this.ytPlayer.getCurrentTime();
        }

        this.wrapperNode.addEventListener('click', this.onOverlayClick.bind(this));
        this.ytPlayer.addEventListener('onStateChange', this.onPlayerStateChange.bind(this));
        setInterval(this.updatePlayerLastTime.bind(this), 5000);
    }

    var ytpwrelWrappers = Array.prototype.slice.call(
        document.querySelectorAll('.ytpwrel-wrapper')
    );

    ytpwrelWrappers.forEach(function(wrapperNode){
        new YtpwrelWrapper(wrapperNode);
    });
}
