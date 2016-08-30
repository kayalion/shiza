{extends file="base/index"}

{block name="head_title" prepend}{$repository->getName()} | {translate key="title.repositories"} | {/block}

{block name="styles" append}
    {style src="bootstrap/css/repository.css"}
{/block}

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

{/block}
