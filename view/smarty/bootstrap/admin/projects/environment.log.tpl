{extends file="base/index"}

{block name="head_title" prepend}{$project->getCode()} | {translate key="title.projects"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>
            {translate key="title.projects"}
            <small>{$project->getName()}</small>
        </h1>
    </div>
{/block}

{block name="content" append}
    <p>
        <a href="{$referer}">
            {translate key="button.back.overview"}
        </a>
    </p>

    <h3>{$environment->getDomain()}</h3>
    <pre>{$log}</pre>
{/block}
