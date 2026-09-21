package com.reflectlink.actionplan;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import java.time.LocalDate;

public class CreateActionPlanRequest {

    @NotNull
    private Integer postId;

    @NotBlank
    private String action;

    private String practiceMenu;

    private LocalDate dueDate;

    @NotBlank
    private String createdByName;

    public Integer getPostId() {
        return postId;
    }

    public void setPostId(Integer postId) {
        this.postId = postId;
    }

    public String getAction() {
        return action;
    }

    public void setAction(String action) {
        this.action = action;
    }

    public String getPracticeMenu() {
        return practiceMenu;
    }

    public void setPracticeMenu(String practiceMenu) {
        this.practiceMenu = practiceMenu;
    }

    public LocalDate getDueDate() {
        return dueDate;
    }

    public void setDueDate(LocalDate dueDate) {
        this.dueDate = dueDate;
    }

    public String getCreatedByName() {
        return createdByName;
    }

    public void setCreatedByName(String createdByName) {
        this.createdByName = createdByName;
    }
}
