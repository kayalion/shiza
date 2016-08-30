{extends file="base/index"}

{block name="head_title" prepend}{$repository->getName()} | {translate key="title.repositories"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>
            {translate key="title.repositories"}
            <small>
                {$repository->getName()}
            </small>
        </h1>
    </div>
{/block}

{block name="content" append}
    {include file="admin/repositories/helper.header" url="repositories.browse"}
    {include file="admin/repositories/helper.browse"}

    {if $mime->isText()}
    <?prettify?>
    <pre class="prettyprint linenums">{$file->read()|htmlentities}</pre>
    {elseif $mime->isImage()}
    <img src="{image src=$file}" class="img-responsive">
    {else}
    binary content
    {/if}
{/block}

{block scripts_app append}
    {script src="http://cdn.rawgit.com/google/code-prettify/master/loader/run_prettify.js?skin=desert"}
{/block}
