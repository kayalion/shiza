{extends file="base/index"}

{block name="styles" append}
    {style src="bootstrap/css/repository.css"}
{/block}

{block name="head_title" prepend}{translate key="title.activity"} | {/block}

{block name="content_title"}
    <div class="page-header">
        <h1>
            {translate key="title.activity"}
            <small>{$message->getTitle()}</small>
        </h1>
    </div>
{/block}

{block name="content" append}
    {$type = $message->getType()}
    {if $type == 'error'}
        {$type = 'danger'}
    {/if}

    <dl class="dl-horizontal">
        <dt>{translate key="label.type"}</dt>
        <dd>
            <span class="label label-{$type}">{$message->getType()}</span>
        </dd>
        <dt>{translate key="label.date"}</dt>
        <dd>
            {$message->getDateAdded()|date_format:"%Y/%m/%d %H:%M:%S"}
        </dd>
        {if $message->getRepository()}
        <dt>{translate key="label.repository"}</dt>
        <dd>
            <a href="{url id="repositories.detail" parameters=["repository" => $message->getRepository()->getSlug()]}">
                {$message->getRepository()->getName()}
            </a>
        </dd>
        {/if}
        {if $message->getProject()}
        <dt>{translate key="label.project"}</dt>
        <dd>
            <a href="{url id="projects.detail" parameters=["project" => $message->getProject()->getCode()]}">
                {$message->getProject()->getCode()}
            </a>
        </dd>
        {/if}
    </dl>

    <p>{$message->getDescription()|replace:" ":"&nbsp;"|nl2br}</p>

    {if $message->getBody()}
    <pre class="console">{$message->getBody()}</pre>
    {/if}
{/block}
