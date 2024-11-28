<!DOCTYPE html>
<html lang="en">
    <head>
        <?php
			// Respects 'Request Desktop Site'
			if (preg_match("/(iPhone|iPod|iPad|Android|BlackBerry)/i", $_SERVER["HTTP_USER_AGENT"])) {
				?><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, viewport-fit=cover"><?php
			}

            // Modify this for a different target location
            $target = 'Barn';
            $descriptor = 'the barn';
            $location = ['lat' => 43.70720223386053, 'lon' => -72.29280809595264];
        ?>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="./favicon/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="./favicon/favicon.svg" />
        <link rel="shortcut icon" href="./favicon/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="./favicon/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="<?php echo $target; ?>" />
        <link rel="manifest" href="./favicon/site.webmanifest" />
        <meta name="theme-color" content="#053f2e">

        <!-- Meta tags -->
        <meta name="robots" content="index, follow, archive">
        <meta name="description" content="Find <?php echo $descriptor; ?>">
        <meta charset="utf-8" />
        <meta http-equiv="Cache-control" content="public">

        <!-- SEO and Semantic Markup -->
        <meta name="twitter:card" content="summary">
        <meta name="twitter:creator" content="@alex_beals">

        <meta property="og:title" content="Find <?php echo $descriptor; ?>">
        <meta property="og:type" content="website">
        <meta property="og:url" content="https://alexbeals.com/projects/barn/">
        <meta property="og:description" content="Which direction is <?php echo $descriptor; ?>?">

        <title>Find <?php echo $descriptor; ?></title>

        <style>
            html {
                height: 100%;

                background: #053f2e;  /* fallback for old browsers */
                background: -webkit-linear-gradient(to top, #243124, #053f2e);  /* Chrome 10-25, Safari 5.1-6 */
                background: linear-gradient(to top, #243124, #053f2e); /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
            }

            body {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;

                font-family: -apple-system, BlinkMacSystemFont, sans-serif;

                text-align: center;
                font-size: 16px;

                margin: 0px;
                height: 100%;
            }

            body, html { overscroll-behavior: none; }

            .wrapper {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .hole {
                position: relative;
                display: flex;
                justify-content: center;
                align-items: center;
                width: 235px;
                height: 235px;
                border-radius: 50%;
                background-color: rgb(217, 255, 110, 13%);

                margin-top: 50px;
            }

            .plus {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -55%);
                color: #d9ff6e;
                font-size: 3em;
                font-weight: 100;

                display: none;
            }

            .pin {
                position: absolute;
                top: -7px;
            }

            #target {
                position: absolute;
                top: 0px;
                width: 100%;
                height: 100%;
            }

            #target svg {
                position: absolute;
                left: calc(50% - 20px);
                top: -10px;
            }

            #target span {
                position: relative;
                top: calc(50% - 1em - 1px);

                font-weight: bold;
                color: #d9ff6e;

                padding: 0 2px;
                z-index: 2;
                border-radius: 9px;
            }

            #compass {
                position: relative;
                width: 210px;
                height: 210px;
                border-radius: 50%;
                background-color: #053f2ecc;
                color: #d9ff6e;
            }

            #compass #n {
                position: absolute;
                top: 5px;
                left: 50%;
                transform: translateX(-50%);
                font-weight: bold;
            }

            #compass #s {
                position: absolute;
                bottom: 5px;
                left: 50%;
                transform: translateX(-50%);
            }

            #compass #w {
                position: absolute;
                top: 50%;
                left: 5px;
                transform: translateY(-50%);
            }

            #compass #e {
                position: absolute;
                top: 50%;
                right: 5px;
                transform: translateY(-50%);
            }

            .header {
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: space-evenly;

                color: #d9ff6e;

                width: 100vw;
            }

            .header div {
                display: flex;
                flex-direction: column;
            }

            .header .title {
                font-size: 1em;
                padding-bottom: 4px;
            }

            .header .value {
                font-size: 2em;
            }

        </style>
        <script>
            /** Constants (or ones that will be derived once) */
            let UPDATE_EVENTS = true;
            const TARGET = <?php echo '{"lat": ' . $location['lat'] . ', "lon": ' . $location['lon'] . '};'; ?>
            let compass, targetSpan;

            // From https://www.movable-type.co.uk/scripts/latlong.html
            function getDistance(lat1, lon1, lat2, lon2) {
                const R = 3958.8; // earth radius in miles
                const φ1 = lat1 * Math.PI/180; // φ, λ in radians
                const φ2 = lat2 * Math.PI/180;
                const Δφ = (lat2-lat1) * Math.PI/180;
                const Δλ = (lon2-lon1) * Math.PI/180;

                const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                        Math.cos(φ1) * Math.cos(φ2) *
                        Math.sin(Δλ/2) * Math.sin(Δλ/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

                return R * c; // in miles
            }

            function getBearing(lat1, lon1, lat2, lon2) {
                const φ1 = lat1 * Math.PI/180; // φ, λ in radians
                const φ2 = lat2 * Math.PI/180;
                const λ1 = lon1 * Math.PI/180;
                const λ2 = lon2 * Math.PI/180;
                const y = Math.sin(λ2-λ1) * Math.cos(φ2);
                const x = Math.cos(φ1)*Math.sin(φ2) -
                        Math.sin(φ1)*Math.cos(φ2)*Math.cos(λ2-λ1);
                const θ = Math.atan2(y, x);
                return (θ*180/Math.PI + 360) % 360; // in degrees
            }

            function getCompassDirection(bearing) {
                const directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
                const index = Math.round(bearing / 45) % 8;
                return directions[index];
            }

            function requestDeviceOrientationPermission() {
                if (typeof DeviceOrientationEvent.requestPermission === 'function') {
                    DeviceOrientationEvent.requestPermission()
                        .then(permissionState => {
                            if (permissionState === 'granted') {
                                window.addEventListener('deviceorientation', handleOrientation);
                            } else {
                                alert('Permission denied, this will not work correctly');
                            }
                        })
                        .catch(console.error);
                } else {
                    // For browsers that don't require permission (e.g., Android or older iOS)
                    window.addEventListener('deviceorientation', handleOrientation);
                }
            }

            function rotateElement(element, degrees) {
                element.style.webkitTransform = 'rotate('+degrees+'deg)'; 
                element.style.mozTransform    = 'rotate('+degrees+'deg)'; 
                element.style.msTransform     = 'rotate('+degrees+'deg)'; 
                element.style.oTransform      = 'rotate('+degrees+'deg)'; 
                element.style.transform       = 'rotate('+degrees+'deg)'; 
            }

            function handleOrientation(event) {
                if (!UPDATE_EVENTS) {
                    return;
                }

                let heading;

                // On Safari (iOS), use webkitCompassHeading for absolute compass heading
                if (event.webkitCompassHeading !== undefined) {
                    heading = event.webkitCompassHeading;  // 0 is North
                } else if (event.alpha !== null) {
                    heading = event.alpha;  // This is relative and not true north
                } else {
                    UPDATE_EVENTS = false;
                    alert('Orientation not supported');
                }

                // Make sure the compass direction points correctly
                rotateElement(compass, -heading);

                // Rotate the text so that it's facing up
                targetSpan.style.top = -50 + (Math.abs(90 - (Math.abs(heading - targetDirection) % 180)) / 90) * 10 + 'px';
                rotateElement(targetSpan, heading - targetDirection);
            }

            let targetDirection = null;

            window.onload = () => {
                compass = document.querySelector('#compass');
                targetSpan = document.querySelector('#target span');

                compass.addEventListener('click', (event) => {
                    if ('geolocation' in navigator) {
                        navigator.geolocation.getCurrentPosition((position) => {
                            // Set the distance display
                            const distance = getDistance(position.coords.latitude, position.coords.longitude, TARGET.lat, TARGET.lon);
                            document.querySelector('#distance .value').innerHTML = Math.round(distance).toLocaleString() + ' miles';

                            // Get the direction of the target
                            targetDirection = getBearing(position.coords.latitude, position.coords.longitude, TARGET.lat, TARGET.lon);
                            // Set the display and move the indicator
                            document.querySelector('#direction .value').innerHTML = getCompassDirection(targetDirection);
                            rotateElement(document.querySelector('#target'), targetDirection);

                            // Handle the title text
                            document.querySelector('.plus').style.display = 'block';
                            targetSpan.innerHTML = '<?php echo $target; ?>';
                            targetSpan.style.position = 'absolute';
                            targetSpan.style.left = 'calc(50% - 21px)';
                        });
                        requestDeviceOrientationPermission();
                    } else {
                        UPDATE_EVENTS = false;
                        alert('Position not supported');
                    }
                });
            };
        </script>
    </head>
    <body>
        <div class="wrapper">
            <div class="header">
                <div id="distance">
                    <span class="title">Distance</span>
                    <span class="value">⊘</span>
                </div>
                <div id="direction">
                    <span class="title">Direction</span>
                    <span class="value">⊘</span>
                </div>
            </div>
            <div class="hole">
                <div class="pin">
                    <svg height="40" width="40">
                        <polygon points="18,0 22,0 22,7, 18,7" fill="rgb(217, 255, 110, 13%)"></polygon>
                    </svg>
                </div>
                <div id="compass">
                    <div id="n">N</div>
                    <div id="e">E</div>
                    <div id="s">S</div>
                    <div id="w">W</div>
                    <div id="target">
                        <svg height="40" width="40">
                            <polygon class="triangle" points="10,10 30,10 20,0" fill="#d9ff6e"></polygon>
                        </svg>
                        <span>Tap to find<br /><?php echo $descriptor; ?></span>
                    </div>
                </div>
                <div class="plus">+</div>
            </div>
        </div>
    </body>
</html>