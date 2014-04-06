<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) (DSP)
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2014 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Partial view for the inner body of the main template
 *
 * @var string        $content
 * @var WebController $this
 */
?>

<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <div class="container-fluid">
        <div class="navbar-header">
            <div class="navbar-left df-logo"><a href="/"><img src="/img/df-apple-touch-icon.png"></a></div>
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span>
            </button>
        </div>
    </div>
</div>

<div class="container-fluid container-inner">
    <?php echo $content; ?>
</div>

<div id="footer">
    <div class="container align-center">
        <p class="footer-text">&copy; <a target="_blank" href="https://www.dreamfactory.com">DreamFactory Software, Inc.</a>
            <?php echo date( 'Y' ); ?>. All Rights Reserved.
        </p>
    </div>
</div>
