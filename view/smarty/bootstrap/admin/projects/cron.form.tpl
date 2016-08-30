{extends file="base/index"}

{block name="head_title" prepend}{$project->getCode()} | {translate key="title.projects"} | {/block}

{block name="styles" append}
    {style src="bootstrap/css/cron.css"}
{/block}

{block name="scripts" append}
    {script src="bootstrap/js/jquery-ui.js"}
    {script src="bootstrap/js/form.js"}
{/block}

{block name="scripts_inline" append}
<script type="text/javascript">
    $(function() {
        $("#crontab-global").toggle();
        $("#crontab-toggle").on('click', function(e) {
            e.preventDefault();

            $("#crontab-global").toggle();
        });
    });
</script>
{/block}

{block name="content_title"}
    <div class="page-header">
        <h1>
            {translate key="title.projects"}
            <small>{$project->getName()}</small>
        </h1>
    </div>
{/block}

{block name="content" append}
    {include file="base/form.prototype"}

    <div class="clearfix">
        <form id="{$form->getId()}" class="form-horizontal col-md-8" action="{$app.url.request}" method="POST" role="form">
            <fieldset>
                {call formRows form=$form}

                <div class="form-group">
                    <div class="col-lg-offset-2 col-lg-10">
                        <input type="submit" class="btn btn-default" value="{if $project->getCronTab()}{translate key="button.update"}{else}{translate key="button.add"}{/if}" />
                    {if $referer}
                        <a class="btn" href="{$referer}">{translate key="button.cancel"}</a>
                    {/if}
                    </div>
                </div>
            </fieldset>
        </form>
    </div>

    {if $cronTab}
    <h3>{translate key="title.cron.global"}</h3>

    <div id="cron-occupation" class="hidden-xs clearfix">
        {$blockWidth = floor($blockTime/60*100)}
        {foreach $occupation as $hour => $minutes}
        <div class="hour">
            <div class="hour-label">{$hour}</div>
            {foreach $minutes as $minute => $numJobs}
            <div class="minute" style="width: {$blockWidth}%">
                {$time="`$hour`:"}
                {if $minute < 10}
                    {$time="`$time`0"}
                {/if}
                {$timeStart="`$time``$minute`"}

                {$minuteStop=$minute+$blockTime}
                {if $minuteStop >= 60}
                    {$hourStop=$hour+1}
                    {if $hourStop == 24}
                        {$hourStop="0"}
                    {/if}
                    {$timeStop="`$hourStop`:00"}
                {else}
                    {$time="`$hour`:"}
                    {if $minuteStop < 10}
                        {$time="`$time`0"}
                    {/if}
                    {$timeStop="`$time``$minuteStop`"}
                {/if}

                {if $maxJobs}
                    {$height=$numJobs/$maxJobs*100}
                {else}
                    {$height=0}
                {/if}

                {$margin=(((100-$height)/50*100) / 2) / 2}
                <div class="graph" style="height: {50-$margin}px" title="{if $numJobs == 1}{translate key="label.cron.bar.1" timeStart=$timeStart timeStop=$timeStop}{else}{translate key="label.cron.bar" timeStart=$timeStart timeStop=$timeStop jobs=$numJobs}{/if}">
                     <div class="bar" style="margin-top: {$margin}px"></div>
                </div>
            </div>
            {/foreach}
        </div>
        {/foreach}
    </div>

    <p><a href="#" id="crontab-toggle">{translate key="button.cron.toggle"}</a></p>
    <pre id="crontab-global">{$cronTab|trim}</pre>

    {/if}
{/block}
